<?php

declare(strict_types=1);

/**
 * Milestone 2 (M2) toggle_permission Adversarial Simulator
 *
 * Direct simulation and stress-testing of toggle_permission execution paths.
 */

class TogglePermissionSimulator
{
    private array $dbRows = [];
    private bool $inTransaction = false;
    private array $transactionBackup = [];
    private ?int $currentUserGroupId = 1;
    private array $loadedPermissions = [];
    private array $response = [];
    private int $responseCode = 200;

    public function __construct()
    {
        $this->reset();
    }

    public function reset(): void
    {
        $this->dbRows = [
            ['id' => 1, 'group_id' => 1, 'mod_id' => 10, 'role_id' => 101],
            ['id' => 2, 'group_id' => 1, 'mod_id' => 10, 'role_id' => 102],
            ['id' => 3, 'group_id' => 2, 'mod_id' => 10, 'role_id' => 101],
        ];
        $this->inTransaction = false;
        $this->transactionBackup = [];
        $this->currentUserGroupId = 1;
        $this->loadedPermissions = [1 => ['mod_user.acl', 'mod_news.edit']];
        $this->response = [];
        $this->responseCode = 200;
    }

    public function getDbRows(): array
    {
        return $this->dbRows;
    }

    public function getLoadedPermissions(): array
    {
        return $this->loadedPermissions;
    }

    public function hasPermission(int $groupId, int $modId, int $roleId): bool
    {
        foreach ($this->dbRows as $row) {
            if ($row['group_id'] === $groupId && $row['mod_id'] === $modId && $row['role_id'] === $roleId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Executes simulated toggle_permission logic matching mod_user/index.php
     */
    public function execute(
        string $httpMethod,
        bool $callerHasAclPermission,
        mixed $groupIdRaw,
        mixed $modIdRaw,
        mixed $roleIdRaw,
        mixed $stateRaw,
        bool $simulateDbError = false
    ): array {
        // 1. Method check
        if ($httpMethod !== 'POST') {
            return [
                'status' => 405,
                'payload' => ['success' => false, 'message' => 'Method Not Allowed']
            ];
        }

        // 2. Auth check
        if (!$callerHasAclPermission) {
            return [
                'status' => 403,
                'payload' => ['success' => false, 'message' => 'Unauthorized']
            ];
        }

        // 3. Param parsing & sanitization
        $groupId = (int) $groupIdRaw;
        $modId = (int) $modIdRaw;
        $roleId = $roleIdRaw;
        $state = $stateRaw;
        $isChecked = ($state === '1' || $state === 'true' || $state === true || $state === 1);

        if ($groupId <= 0 || $modId <= 0) {
            return [
                'status' => 400,
                'payload' => ['success' => false, 'message' => 'Invalid module or group ID']
            ];
        }

        $permCol = 'role_id';

        // 4. Batch vs Single handling
        if (is_array($roleId)) {
            $roleIds = array_map('intval', $roleId);
            $roleIds = array_values(array_filter($roleIds, fn($id) => $id > 0));
            if (empty($roleIds)) {
                return [
                    'status' => 400,
                    'payload' => ['success' => false, 'message' => 'No valid role IDs specified']
                ];
            }

            // Start transaction
            $this->startTransaction();

            if ($simulateDbError) {
                $this->rollback();
                return [
                    'status' => 500,
                    'payload' => ['success' => false, 'message' => 'Database error']
                ];
            }

            // DELETE existing matching roles
            $this->dbRows = array_values(array_filter($this->dbRows, function ($row) use ($groupId, $modId, $roleIds) {
                return !($row['group_id'] === $groupId && $row['mod_id'] === $modId && in_array($row['role_id'], $roleIds, true));
            }));

            // INSERT if isChecked
            if ($isChecked) {
                foreach ($roleIds as $rId) {
                    $this->dbRows[] = [
                        'id' => count($this->dbRows) + 1,
                        'group_id' => $groupId,
                        'mod_id' => $modId,
                        'role_id' => $rId
                    ];
                }
            }

            $this->commit();
        } elseif ($roleId !== null && (int)$roleId > 0) {
            $rId = (int) $roleId;

            $this->startTransaction();

            if ($simulateDbError) {
                $this->rollback();
                return [
                    'status' => 500,
                    'payload' => ['success' => false, 'message' => 'Database error']
                ];
            }

            // DELETE existing
            $this->dbRows = array_values(array_filter($this->dbRows, function ($row) use ($groupId, $modId, $rId) {
                return !($row['group_id'] === $groupId && $row['mod_id'] === $modId && $row['role_id'] === $rId);
            }));

            // INSERT if isChecked
            if ($isChecked) {
                $this->dbRows[] = [
                    'id' => count($this->dbRows) + 1,
                    'group_id' => $groupId,
                    'mod_id' => $modId,
                    'role_id' => $rId
                ];
            }

            $this->commit();
        } else {
            return [
                'status' => 400,
                'payload' => ['success' => false, 'message' => 'Invalid role ID']
            ];
        }

        // Live session sync
        if ($this->currentUserGroupId === $groupId) {
            $this->reloadSessionPermissions($groupId);
        }

        return [
            'status' => 200,
            'payload' => [
                'success' => true,
                'message' => 'Permissions updated',
                'group_id' => $groupId,
                'mod_id' => $modId
            ]
        ];
    }

    private function startTransaction(): void
    {
        $this->inTransaction = true;
        $this->transactionBackup = $this->dbRows;
    }

    private function commit(): void
    {
        $this->inTransaction = false;
        $this->transactionBackup = [];
    }

    private function rollback(): void
    {
        if ($this->inTransaction) {
            $this->dbRows = $this->transactionBackup;
            $this->inTransaction = false;
            $this->transactionBackup = [];
        }
    }

    private function reloadSessionPermissions(int $groupId): void
    {
        $perms = [];
        foreach ($this->dbRows as $row) {
            if ($row['group_id'] === $groupId) {
                $perms[] = "mod_{$row['mod_id']}.role_{$row['role_id']}";
            }
        }
        $this->loadedPermissions[$groupId] = $perms;
    }
}

// -----------------------------------------------------------------------------
// Test Runner
// -----------------------------------------------------------------------------

echo "========================================================================\n";
echo "RUNNING EMPIRICAL TOGGLE_PERMISSION ADVERSARIAL SIMULATION HARNESS\n";
echo "========================================================================\n\n";

$sim = new TogglePermissionSimulator();
$passed = 0;
$failed = 0;

function runTest(bool $condition, string $msg, int &$p, int &$f) {
    if ($condition) {
        echo "  [PASS] " . $msg . "\n";
        $p++;
    } else {
        echo "  [FAIL] " . $msg . "\n";
        $f++;
    }
}

// Test 1: Method rejection
$res = $sim->execute('GET', true, 1, 10, 101, '1');
runTest($res['status'] === 405 && $res['payload']['success'] === false, "Reject GET with 405 Method Not Allowed", $passed, $failed);

$res = $sim->execute('DELETE', true, 1, 10, 101, '0');
runTest($res['status'] === 405 && $res['payload']['success'] === false, "Reject DELETE with 405 Method Not Allowed", $passed, $failed);

// Test 2: Unauthorized caller
$res = $sim->execute('POST', false, 1, 10, 101, '1');
runTest($res['status'] === 403 && $res['payload']['success'] === false, "Reject unauthorized caller with 403", $passed, $failed);

// Test 3: Invalid group_id or mod_id
$res = $sim->execute('POST', true, 0, 10, 101, '1');
runTest($res['status'] === 400 && $res['payload']['success'] === false, "Reject group_id=0 with 400", $passed, $failed);

$res = $sim->execute('POST', true, -5, 10, 101, '1');
runTest($res['status'] === 400 && $res['payload']['success'] === false, "Reject negative group_id with 400", $passed, $failed);

$res = $sim->execute('POST', true, 1, 0, 101, '1');
runTest($res['status'] === 400 && $res['payload']['success'] === false, "Reject mod_id=0 with 400", $passed, $failed);

$res = $sim->execute('POST', true, 'invalid_str', 10, 101, '1');
runTest($res['status'] === 400 && $res['payload']['success'] === false, "Reject non-numeric string group_id with 400", $passed, $failed);

// Test 4: Invalid role_id
$res = $sim->execute('POST', true, 1, 10, 0, '1');
runTest($res['status'] === 400 && $res['payload']['success'] === false, "Reject role_id=0 with 400", $passed, $failed);

$res = $sim->execute('POST', true, 1, 10, null, '1');
runTest($res['status'] === 400 && $res['payload']['success'] === false, "Reject null role_id with 400", $passed, $failed);

$res = $sim->execute('POST', true, 1, 10, [], '1');
runTest($res['status'] === 400 && $res['payload']['success'] === false, "Reject empty role_id[] array with 400", $passed, $failed);

$res = $sim->execute('POST', true, 1, 10, [0, -1, "abc"], '1');
runTest($res['status'] === 400 && $res['payload']['success'] === false, "Reject role_id[] with only invalid numbers with 400", $passed, $failed);

// Test 5: Single toggle off (101 is currently ON for group 1, mod 10)
$sim->reset();
runTest($sim->hasPermission(1, 10, 101) === true, "Initial state: group 1, mod 10, role 101 is ON", $passed, $failed);
$res = $sim->execute('POST', true, 1, 10, 101, '0');
runTest($res['status'] === 200 && $res['payload']['success'] === true, "Single toggle OFF returns 200 Success", $passed, $failed);
runTest($sim->hasPermission(1, 10, 101) === false, "Database state: group 1, mod 10, role 101 is now OFF", $passed, $failed);

// Test 6: Single toggle on (103 is currently OFF for group 1, mod 10)
runTest($sim->hasPermission(1, 10, 103) === false, "Initial state: group 1, mod 10, role 103 is OFF", $passed, $failed);
$res = $sim->execute('POST', true, 1, 10, 103, '1');
runTest($res['status'] === 200 && $res['payload']['success'] === true, "Single toggle ON returns 200 Success", $passed, $failed);
runTest($sim->hasPermission(1, 10, 103) === true, "Database state: group 1, mod 10, role 103 is now ON", $passed, $failed);

// Test 7: Batch toggle Grant All (roles 101, 102, 103, 104)
$sim->reset();
$res = $sim->execute('POST', true, 1, 10, [101, 102, 103, 104], '1');
runTest($res['status'] === 200 && $res['payload']['success'] === true, "Batch Grant All returns 200 Success", $passed, $failed);
runTest(
    $sim->hasPermission(1, 10, 101) && $sim->hasPermission(1, 10, 102) && $sim->hasPermission(1, 10, 103) && $sim->hasPermission(1, 10, 104),
    "Batch Grant All grants all 4 roles in database",
    $passed,
    $failed
);

// Test 8: Batch toggle Revoke All
$res = $sim->execute('POST', true, 1, 10, [101, 102, 103, 104], '0');
runTest($res['status'] === 200 && $res['payload']['success'] === true, "Batch Revoke All returns 200 Success", $passed, $failed);
runTest(
    !$sim->hasPermission(1, 10, 101) && !$sim->hasPermission(1, 10, 102) && !$sim->hasPermission(1, 10, 103) && !$sim->hasPermission(1, 10, 104),
    "Batch Revoke All revokes all 4 roles in database",
    $passed,
    $failed
);

// Test 9: Isolation of other groups during batch update
runTest($sim->hasPermission(2, 10, 101) === true, "Group 2 permissions remain completely untouched and preserved", $passed, $failed);

// Test 10: Transaction rollback on simulated error
$sim->reset();
$initialRows = $sim->getDbRows();
$res = $sim->execute('POST', true, 1, 10, [101, 102], '0', true); // DB error
runTest($res['status'] === 500 && $res['payload']['success'] === false, "Database error returns 500", $passed, $failed);
runTest($sim->getDbRows() === $initialRows, "Transaction rollback restores exact initial state on error", $passed, $failed);

// Test 11: SQL injection resiliency in parameters
$res = $sim->execute('POST', true, "1 OR 1=1", 10, 101, '1');
runTest($res['status'] === 200, "Integer cast prevents SQL injection in group_id ('1 OR 1=1' -> 1)", $passed, $failed);

$res = $sim->execute('POST', true, 1, 10, ["101; DROP TABLE mod_user_perm;--", "102"], '1');
runTest($res['status'] === 200 && $sim->hasPermission(1, 10, 101) && $sim->hasPermission(1, 10, 102), "Integer cast sanitizes array injection attempts safely", $passed, $failed);

echo "\n========================================================================\n";
echo "SIMULATION RESULTS: Total={$passed}+{$failed}, Passed={$passed}, Failed={$failed}\n";
echo "========================================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
