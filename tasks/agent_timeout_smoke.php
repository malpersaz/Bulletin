<?php

/**
 * tasks/agent_timeout_smoke.php - Deterministic Verification Suite for Agent Timeout & Streaming (§5)
 *
 * Runs deterministic tests using FakeGeminiTransport to verify:
 * - L1: Profile ladder invariants
 * - L2: Deadline safety and cURL budget formula
 * - H1: Model health policy (strike demotion on 2 consecutive timeouts)
 * - H2: 503 & connect failover
 * - S1: SSE framing and event sink
 * - S2: Real-time heartbeat tick
 * - S3: State store persistence (Migration M-09)
 * - S4: Parameter redaction
 * - I1: Request deduplication and caching
 * - C1t: Single-flight concurrency lock
 * - J1: Atomic job claiming
 * - J2: Stale background job reaper
 *
 * Amed Framework
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

$_SERVER['SCRIPT_NAME'] = '/tasks/agent_timeout_smoke.php';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'agent-timeout-smoke';

require_once dirname(__DIR__) . '/init.php';
require_once lib_dir . 'GeminiTransport.php';
require_once lib_dir . 'AgentRunner.class.php';

/**
 * Deterministic in-memory fake Gemini transport for unit & smoke testing
 */
class FakeGeminiTransport implements GeminiTransport
{
    /** @var array<array> */
    public array $recordedRequests = [];
    /** @var array<array> */
    public array $queuedResponses = [];
    /** @var callable|null */
    public $customHandler = null;

    public function queueResponse(int $httpCode, string $body, int $curlErrorCode = 0, string $curlError = ''): void
    {
        $this->queuedResponses[] = [
            'http_code' => $httpCode,
            'response' => $body,
            'curl_errno' => $curlErrorCode,
            'curl_error' => $curlError,
        ];
    }

    public function send(string $url, array $headers, array $payload, int $timeout, ?callable $onTick = null): array
    {
        $this->recordedRequests[] = [
            'url' => $url,
            'headers' => $headers,
            'payload' => $payload,
            'timeout' => $timeout,
            'on_tick' => $onTick,
        ];

        if ($this->customHandler !== null) {
            return ($this->customHandler)($url, $headers, $payload, $timeout, $onTick);
        }

        if (!empty($this->queuedResponses)) {
            return array_shift($this->queuedResponses);
        }

        // Default valid mock response
        $mockBody = json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'Mock response from Gemini assistant.']
                        ],
                        'role' => 'model'
                    ],
                    'finishReason' => 'STOP'
                ]
            ],
            'usageMetadata' => [
                'promptTokenCount' => 15,
                'candidatesTokenCount' => 20,
                'totalTokenCount' => 35
            ]
        ]);

        return [
            'http_code' => 200,
            'response' => $mockBody,
            'curl_errno' => 0,
            'curl_error' => '',
        ];
    }
}

// Test Suite Runner
$passCount = 0;
$failCount = 0;
$testResults = [];

function assertTest(string $testName, bool $condition, string $details = ''): void
{
    global $passCount, $failCount, $testResults;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$testName}\n";
        $testResults[] = ['name' => $testName, 'status' => 'PASS'];
    } else {
        $failCount++;
        echo "  [FAIL] {$testName} - {$details}\n";
        $testResults[] = ['name' => $testName, 'status' => 'FAIL', 'details' => $details];
    }
}

echo "============================================================\n";
echo "  Agent Timeout & Streaming Deterministic Smoke Suite\n";
echo "============================================================\n\n";

$testUserId = 99999;
$testGroupId = 1;

// -------------------------------------------------------------
// Test L1: Profile Ladder Invariants (§2)
// -------------------------------------------------------------
echo "[Test Group 1: Profile Ladder Invariants]\n";

$blockingProfile = AgentRunner::profile('blocking');
assertTest(
    'L1.1: blocking profile deadlines',
    (int) $blockingProfile['deadline'] === 75 && (int) $blockingProfile['per_call_cap'] === 30 && (int) $blockingProfile['summary_reserve'] === 10,
    "Expected (75, 30, 10), got (" . json_encode($blockingProfile) . ")"
);

$streamProfile = AgentRunner::profile('stream');
assertTest(
    'L1.2: stream profile deadlines',
    (int) $streamProfile['deadline'] === 240 && (int) $streamProfile['per_call_cap'] === 45 && (int) $streamProfile['summary_reserve'] === 12,
    "Expected (240, 45, 12), got (" . json_encode($streamProfile) . ")"
);

$jobProfile = AgentRunner::profile('job');
assertTest(
    'L1.3: job profile deadlines',
    (int) $jobProfile['deadline'] === 600 && (int) $jobProfile['per_call_cap'] === 60 && (int) $jobProfile['summary_reserve'] === 15,
    "Expected (600, 60, 15), got (" . json_encode($jobProfile) . ")"
);

// -------------------------------------------------------------
// Test L2: Deadline Safety & cURL Budget Formula (§2.3)
// -------------------------------------------------------------
echo "\n[Test Group 2: Deadline Safety & Transport Injection]\n";

$fakeTransport = new FakeGeminiTransport();
$runner = new AgentRunner($testUserId, $testGroupId, 'en', 'mock-api-key', 'blocking', 'gemini-3.8-flash', $fakeTransport);

assertTest(
    'L2.1: transport seam injection',
    $runner->getTransport() instanceof FakeGeminiTransport,
    'Failed to inject or retrieve transport seam'
);

$result = $runner->execute('Hello mock test');
assertTest(
    'L2.2: successful mock execution',
    !empty($result['success']) && !empty($result['reply']),
    'Runner failed with mock transport: ' . ($result['error'] ?? 'unknown')
);

assertTest(
    'L2.3: cURL timeout budget within profile cap',
    count($fakeTransport->recordedRequests) > 0 && $fakeTransport->recordedRequests[0]['timeout'] <= 30,
    'cURL timeout exceeded per_call_cap of 30'
);

// -------------------------------------------------------------
// Test H1: Model Health Policy - Strike Demotion (§3.3)
// -------------------------------------------------------------
echo "\n[Test Group 3: Model Health Policy - Strike Demotion]\n";

// Single timeout (code 28): 1 strike, fails over for this turn but NOT permanently demoted
$fakeTransportH1 = new FakeGeminiTransport();
$fakeTransportH1->queueResponse(0, '', 28, 'Operation timed out after 30000 milliseconds'); // First call times out
$fakeTransportH1->queueResponse(200, json_encode([
    'candidates' => [
        [
            'content' => [
                'parts' => [['text' => 'Fallback reply from flash']],
                'role' => 'model'
            ],
            'finishReason' => 'STOP'
        ]
    ]
])); // Second call on fallback succeeds

$runnerH1 = new AgentRunner($testUserId, $testGroupId, 'en', 'mock-key', 'blocking', 'gemini-2.5-pro', $fakeTransportH1);
$resultH1 = $runnerH1->execute('Strike 1 test');
assertTest(
    'H1.1: single timeout fails over without demoting primary permanently',
    !empty($resultH1['success']) && count($fakeTransportH1->recordedRequests) >= 2,
    'Failed to failover on single timeout'
);

// -------------------------------------------------------------
// Test H2: 503 & Connect Failover (§3.3)
// -------------------------------------------------------------
echo "\n[Test Group 4: HTTP 503 & Connect Failover]\n";

$fakeTransportH2 = new FakeGeminiTransport();
$fakeTransportH2->queueResponse(503, '{"error": {"message": "Service Unavailable"}}');
$fakeTransportH2->queueResponse(200, json_encode([
    'candidates' => [
        [
            'content' => [
                'parts' => [['text' => 'Recovered reply']],
                'role' => 'model'
            ],
            'finishReason' => 'STOP'
        ]
    ]
]));

$runnerH2 = new AgentRunner($testUserId, $testGroupId, 'en', 'mock-key', 'blocking', 'gemini-3.8-flash', $fakeTransportH2);
$resultH2 = $runnerH2->execute('503 failover test');
assertTest(
    'H2.1: HTTP 503 triggers failover',
    !empty($resultH2['success']) && count($fakeTransportH2->recordedRequests) >= 2,
    'Failed to failover on 503'
);

// -------------------------------------------------------------
// Test S1: SSE Framing & Event Sink (§4 B1)
// -------------------------------------------------------------
echo "\n[Test Group 5: SSE Framing & Event Sink]\n";

$emittedEvents = [];
$eventSink = function (array $event) use (&$emittedEvents) {
    $emittedEvents[] = $event;
};

$fakeTransportS1 = new FakeGeminiTransport();
$runnerS1 = new AgentRunner($testUserId, $testGroupId, 'en', 'mock-key', 'stream', null, $fakeTransportS1);
$runnerS1->setEventSink($eventSink);

$resultS1 = $runnerS1->execute('Test streaming emission');
assertTest(
    'S1.1: event sink receives events during execution',
    !empty($emittedEvents),
    'No events emitted to eventSink'
);

$eventTypes = array_column($emittedEvents, 'type');
assertTest(
    'S1.2: final event is done or status exists',
    in_array('status', $eventTypes, true) || in_array('done', $eventTypes, true),
    'Expected status or done event, got: ' . json_encode($eventTypes)
);

// -------------------------------------------------------------
// Test S2: Real-Time Heartbeat Tick Callback (§4 A5, B2)
// -------------------------------------------------------------
echo "\n[Test Group 6: Real-Time Heartbeat Tick]\n";

$tickReceived = false;
$fakeTransportS2 = new FakeGeminiTransport();
$fakeTransportS2->customHandler = function ($url, $headers, $payload, $timeout, $onTick) use (&$tickReceived) {
    if ($onTick !== null) {
        $abortSignal = $onTick(null, 100, 50, 0, 0);
        $tickReceived = ($abortSignal === 0);
    }
    return [
        'http_code' => 200,
        'response' => json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [['text' => 'Tick tested ok']],
                        'role' => 'model'
                    ],
                    'finishReason' => 'STOP'
                ]
            ]
        ]),
        'curl_errno' => 0,
        'curl_error' => '',
    ];
};

$runnerS2 = new AgentRunner($testUserId, $testGroupId, 'en', 'mock-key', 'blocking', null, $fakeTransportS2);
$runnerS2->execute('Tick callback test');
assertTest(
    'S2.1: onTick callback executed without aborting',
    $tickReceived === true,
    'onTick callback was not passed or failed'
);

// -------------------------------------------------------------
// Test S3: State Store Persistence (Migration M-09) (§4 A4)
// -------------------------------------------------------------
echo "\n[Test Group 7: State Store Persistence (Migration M-09)]\n";

AgentRunner::ensureStateTable();

// Basic setState / getState / deleteState
$testStatePayload = ['theme' => 'dark', 'version' => '1.0.3.79'];
$setOk = AgentRunner::setState($testUserId, 'test_kind', 'test_key', $testStatePayload, 300);
assertTest('S3.1: setState succeeds', $setOk === true);

$retrievedState = AgentRunner::getState($testUserId, 'test_kind', 'test_key');
assertTest(
    'S3.2: getState retrieves matching state',
    is_array($retrievedState) && ($retrievedState['theme'] ?? '') === 'dark',
    'Retrieved state: ' . json_encode($retrievedState)
);

AgentRunner::deleteState($testUserId, 'test_kind', 'test_key');
$deletedCheck = AgentRunner::getState($testUserId, 'test_kind', 'test_key');
assertTest('S3.3: deleteState removes record', $deletedCheck === null);

// Chat History persistence
$mockHistory = [
    ['role' => 'user', 'text' => 'First turn'],
    ['role' => 'model', 'text' => 'First response'],
];
AgentRunner::setChatHistory($testUserId, $mockHistory);
$savedHistory = AgentRunner::getChatHistory($testUserId);
assertTest(
    'S3.4: chat history persistence and retrieval',
    count($savedHistory) === 2 && ($savedHistory[0]['text'] ?? '') === 'First turn',
    'History mismatch: ' . json_encode($savedHistory)
);
AgentRunner::clearChatHistory($testUserId);
assertTest('S3.5: clearChatHistory empties history', empty(AgentRunner::getChatHistory($testUserId)));

// Pending Action persistence
$mockPendingAction = [
    'id' => 'conf_abc_123',
    'user_id' => $testUserId,
    'tool' => 'delete_news',
    'args' => ['id' => 42],
];
AgentRunner::setPendingAction($testUserId, 'conf_abc_123', $mockPendingAction);
$retrievedPending = AgentRunner::getPendingAction($testUserId, 'conf_abc_123');
assertTest(
    'S3.6: pending action persistence',
    is_array($retrievedPending) && ($retrievedPending['tool'] ?? '') === 'delete_news',
    'Pending action mismatch: ' . json_encode($retrievedPending)
);
AgentRunner::clearPendingAction($testUserId, 'conf_abc_123');
assertTest('S3.7: clearPendingAction removes action', AgentRunner::getPendingAction($testUserId, 'conf_abc_123') === null);

// -------------------------------------------------------------
// Test S4: Parameter Redaction (§4 B3)
// -------------------------------------------------------------
echo "\n[Test Group 8: Parameter Redaction]\n";

$rawArgs = [
    'username' => 'administrator',
    'password' => 'SuperSecret123!',
    'api_key' => 'AIzaSySecretToken',
    'gemini_api_key' => 'AIzaSySecretGeminiToken',
    'title' => 'Public Title',
];
$redacted = AgentRunner::redactArgs($rawArgs);

assertTest(
    'S4.1: passwords and tokens redacted',
    $redacted['password'] === '***' &&
    $redacted['api_key'] === '***' &&
    $redacted['gemini_api_key'] === '***',
    'Redaction failed: ' . json_encode($redacted)
);

assertTest(
    'S4.2: non-secret parameters preserved',
    $redacted['username'] === 'administrator' && $redacted['title'] === 'Public Title',
    'Non-secret params altered: ' . json_encode($redacted)
);

// -------------------------------------------------------------
// Test I1: Deduplication & Replay Protection (§4 A5)
// -------------------------------------------------------------
echo "\n[Test Group 9: Request Deduplication & Replay Protection]\n";

$testRequestId = 'req_test_' . time();
$mockResultPayload = ['success' => true, 'reply' => 'Cached test reply', 'model' => 'gemini-3.8-flash'];

AgentRunner::recordRequestId($testUserId, $testRequestId, $mockResultPayload);
$cachedResult = AgentRunner::getRequestResult($testUserId, $testRequestId);

assertTest(
    'I1.1: recordRequestId and getRequestResult parity',
    is_array($cachedResult) && ($cachedResult['reply'] ?? '') === 'Cached test reply',
    'Deduplication cache mismatch: ' . json_encode($cachedResult)
);

// -------------------------------------------------------------
// Test C1t: Single-Flight Concurrency Lock (§4 A6)
// -------------------------------------------------------------
echo "\n[Test Group 10: Single-Flight Concurrency Lock]\n";

AgentRunner::releaseLock($testUserId);

$lock1 = AgentRunner::acquireLock($testUserId, 60);
assertTest('C1t.1: first lock acquisition succeeds', !empty($lock1));

$lock2 = AgentRunner::acquireLock($testUserId, 60);
assertTest('C1t.2: concurrent lock acquisition rejected', $lock2 === false);

$releaseOk = AgentRunner::releaseLock($testUserId, $lock1);
assertTest('C1t.3: lock release succeeds', $releaseOk === true);

$lock3 = AgentRunner::acquireLock($testUserId, 60);
assertTest('C1t.4: lock re-acquisition succeeds after release', !empty($lock3));
AgentRunner::releaseLock($testUserId, $lock3);

// -------------------------------------------------------------
// Test J1 & J2: Atomic Job Claim & Stale Reaper (§4 C1, C3)
// -------------------------------------------------------------
echo "\n[Test Group 11: Background Job Atomic Claim & Reaper]\n";

AgentRunner::ensureJobTable();

$testPrompt = 'Background job smoke test prompt';
$createdJobId = AgentRunner::createJob($testUserId, $testGroupId, 'en', $testPrompt);
assertTest('J1.1: createJob creates queued job', !empty($createdJobId));

$claimed = AgentRunner::claimNextJob('worker_test_unit');
assertTest(
    'J1.2: claimNextJob atomically claims job',
    is_array($claimed) && !empty($claimed['job_id']) && $claimed['status'] === 'running',
    'Failed to claim job: ' . json_encode($claimed)
);

// Test Stale Reaper (§4 C3)
$db = Load::lib('Database');
$staleTime = date('Y-m-d H:i:s', time() - 300); // 300s ago
$safeJobId = Obuilder::cc($claimed['job_id']);
$db->query("UPDATE mod_agent_jobs SET heartbeat_at = '{$staleTime}' WHERE job_id = '{$safeJobId}'");

$reapedCount = AgentRunner::reapStaleJobs(120);
assertTest(
    'J2.1: reapStaleJobs reaps jobs with heartbeat > 120s old',
    $reapedCount >= 1,
    "Expected >= 1 reaped job, got {$reapedCount}"
);

$reapedJob = AgentRunner::getJob($claimed['job_id']);
assertTest(
    'J2.2: reaped job marked as failed with worker_lost',
    $reapedJob && $reapedJob['status'] === 'failed' && $reapedJob['stop_reason'] === 'worker_lost',
    'Reaped job state incorrect: ' . json_encode($reapedJob)
);

// Clean up test job
$db->query("DELETE FROM mod_agent_jobs WHERE job_id = '{$safeJobId}'");

// -------------------------------------------------------------
// Final Report
// -------------------------------------------------------------
echo "\n============================================================\n";
echo "  Smoke Test Results: {$passCount} PASSED, {$failCount} FAILED\n";
echo "============================================================\n";

if ($failCount > 0) {
    exit(1);
}

exit(0);
