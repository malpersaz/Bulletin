<?php

require_once __DIR__ . '/HttpClient.class.php';

/**
 * Amed Framework
 * Class: AIService
 * File: AIService.class.php
 * Description: Unified Google Gemini AI Service (generateContent & Interactions API client)
 */

class AIService
{
    private string $apiKey = '';
    private string $model = 'gemini-3.8-flash';
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/interactions';
    private array $fallbackModels = ['gemini-3.8-flash', 'gemini-3.6-flash', 'gemini-2.5-flash'];

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        if (!empty($apiKey)) {
            $this->apiKey = trim($apiKey);
        } else {
            $googleConfig = Registry::has('google_config') ? Registry::get('google_config') : [];
            if (!empty($googleConfig['gemini_api_key'])) {
                $this->apiKey = trim((string) $googleConfig['gemini_api_key']);
            }

            if (empty($this->apiKey)) {
                $site = Registry::has('site_config') ? Registry::get('site_config') : [];
                if (!empty($site['gemini_api_key'])) {
                    $this->apiKey = trim((string) $site['gemini_api_key']);
                }
            }

            if (empty($this->apiKey)) {
                $settingsPath = root_dir . 'data' . DS . 'google_settings.json';
                if (file_exists($settingsPath)) {
                    $raw = json_decode((string) file_get_contents($settingsPath), true);
                    if (!empty($raw['gemini_api_key'])) {
                        $this->apiKey = trim((string) $raw['gemini_api_key']);
                    }
                }
            }
        }

        if (!empty($model)) {
            $this->model = $model;
        } else {
            $site = Registry::has('site_config') ? Registry::get('site_config') : [];
            $this->model = !empty($site['gemini_model']) ? (string) $site['gemini_model'] : 'gemini-3.8-flash';
        }
    }

    public function hasApiKey(): bool
    {
        return !empty($this->apiKey);
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Executes resilient Gemini API calls with automated model fallback and transient retry
     */
    public function generateContent(array $payload, ?string $preferredModel = null): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'Gemini API Key is not configured in Google Services Hub.'
            ];
        }

        $modelsToTry = $this->fallbackModels;
        if (!empty($preferredModel)) {
            array_unshift($modelsToTry, $preferredModel);
            $modelsToTry = array_values(array_unique($modelsToTry));
        } elseif (!empty($this->model) && !in_array($this->model, $modelsToTry, true)) {
            array_unshift($modelsToTry, $this->model);
        }

        $lastError = '';

        foreach ($modelsToTry as $modelName) {
            $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/' . $modelName . ':generateContent?key=' . $this->apiKey;

            for ($attempt = 0; $attempt < 2; $attempt++) {
                if ($attempt > 0) {
                    usleep(400000); // 400ms pause before retry
                }

                $httpResult = HttpClient::postJson($apiUrl, $payload, [], 30);
                $httpCode = $httpResult['status'];
                $curlErr = $httpResult['error'];

                if ($curlErr !== '') {
                    $lastError = 'Connection error (cURL): ' . $curlErr;
                    continue;
                }

                $resData = $httpResult['json'];
                if ($httpCode === 200 && !empty($resData['candidates'])) {
                    $text = $resData['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    return [
                        'success' => true,
                        'data' => $resData,
                        'text' => $text,
                        'model' => $modelName,
                    ];
                }

                if (($httpCode === 503 || $httpCode === 429) && $attempt === 0) {
                    continue;
                }

                if (!empty($resData['error']['message'])) {
                    $lastError = '[' . $modelName . '] ' . $resData['error']['message'];
                } elseif ($httpCode !== 200) {
                    $lastError = '[' . $modelName . '] Google API Error (HTTP ' . $httpCode . ')';
                }
                break;
            }
        }

        return [
            'success' => false,
            'error' => !empty($lastError) ? $lastError : 'No response received from Gemini API.',
        ];
    }

    /**
     * Generates a broadsheet news deck strictly matching AKM Bulletin CMS's 90-120 character constraint
     */
    public function generateDeck(string $title, string $content, string $lang = 'ku'): array
    {
        $langNames = [
            'ku' => 'Kurmanji Kurdish (Latin alphabet, authentic Kurmancî grammar and orthography)',
            'ckb' => 'Sorani Kurdish (Arabic script)',
            'zza' => 'Zazaki Kurdish (Kirmanckî)',
            'en' => 'English',
            'tr' => 'Turkish',
            'de' => 'German',
            'fr' => 'French',
            'sv' => 'Swedish',
            'ar' => 'Arabic'
        ];
        $targetLang = $langNames[strtolower($lang)] ?? 'Kurmanji Kurdish';

        $prompt = "You are a senior news copy-editor for a premier broadsheet newspaper (AKM Bulletin CMS).\n"
            . "Task: Analyze the news article provided below and write an editorial deck / summary in {$targetLang}.\n"
            . "CRITICAL MANDATORY CONSTRAINT:\n"
            . "- The total length of the deck MUST BE EXACTLY between 90 and 120 characters (including letters, spaces, and punctuation).\n"
            . "- It must be a complete, grammatically sound sentence.\n"
            . "- Do not include any meta text or quotes around the deck.\n\n"
            . "Title: " . $title . "\n"
            . "Content: " . strip_tags($content);

        $payload = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'deck' => ['type' => 'STRING'],
                        'char_count' => ['type' => 'INTEGER']
                    ],
                    'required' => ['deck']
                ],
                'temperature' => 0.3
            ]
        ];

        $res = $this->generateContent($payload);
        if (!$res['success'] || empty($res['text'])) {
            return $res;
        }

        $decoded = json_decode($res['text'], true);
        $deck = trim((string) ($decoded['deck'] ?? ''));

        // Smart calibration if slightly outside bounds
        $len = mb_strlen($deck);
        if ($len > 120) {
            // Trim to nearest word within 120 chars
            $trimmed = mb_substr($deck, 0, 119);
            $lastSpace = mb_strrpos($trimmed, ' ');
            if ($lastSpace !== false && $lastSpace >= 90) {
                $deck = mb_substr($trimmed, 0, $lastSpace) . '.';
            } else {
                $deck = mb_substr($deck, 0, 119) . '.';
            }
        }

        return [
            'success' => true,
            'deck' => $deck,
            'length' => mb_strlen($deck),
            'model' => $res['model']
        ];
    }

    /**
     * Suggests 3 compelling broadsheet headlines for news
     */
    public function suggestHeadlines(string $content, string $currentTitle = '', string $lang = 'ku'): array
    {
        $langNames = [
            'ku' => 'Kurmanji Kurdish (Latin alphabet, authentic Kurmancî vocabulary)',
            'ckb' => 'Sorani Kurdish',
            'zza' => 'Zazaki Kurdish',
            'en' => 'English',
            'tr' => 'Turkish',
            'de' => 'German',
            'fr' => 'French',
            'sv' => 'Swedish',
            'ar' => 'Arabic'
        ];
        $targetLang = $langNames[strtolower($lang)] ?? 'Kurmanji Kurdish';

        $prompt = "You are a chief headline editor for a respected broadsheet news publication.\n"
            . "Read the article and provide 3 distinct, strong, and journalistic headlines in {$targetLang}.\n"
            . "Headlines should be clear, objective, and impactful without clickbait.\n\n"
            . (!empty($currentTitle) ? "Current Working Title: " . $currentTitle . "\n" : "")
            . "Content: " . strip_tags($content);

        $payload = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'headlines' => [
                            'type' => 'ARRAY',
                            'items' => ['type' => 'STRING']
                        ]
                    ],
                    'required' => ['headlines']
                ],
                'temperature' => 0.4
            ]
        ];

        $res = $this->generateContent($payload);
        if (!$res['success'] || empty($res['text'])) {
            return $res;
        }

        $decoded = json_decode($res['text'], true);
        $headlines = is_array($decoded['headlines'] ?? null) ? $decoded['headlines'] : [];

        return [
            'success' => true,
            'headlines' => array_values(array_filter(array_map('trim', $headlines))),
            'model' => $res['model']
        ];
    }

    /**
     * Generates structured SEO metadata (Title, Description, Keywords)
     */
    public function generateSeoMeta(string $title, string $content, string $lang = 'ku'): array
    {
        $prompt = "Analyze the following content and generate an SEO title, a meta description (max 160 characters), and relevant keywords/tags. Return output strictly matching the requested JSON schema.\n\n"
            . "Title: " . $title . "\n"
            . "Content: " . strip_tags($content);

        $payload = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'seo_title' => ['type' => 'STRING'],
                        'meta_description' => ['type' => 'STRING'],
                        'keywords' => [
                            'type' => 'ARRAY',
                            'items' => ['type' => 'STRING']
                        ]
                    ],
                    'required' => ['seo_title', 'meta_description', 'keywords']
                ],
                'temperature' => 0.3
            ]
        ];

        $res = $this->generateContent($payload);
        if (!$res['success'] || empty($res['text'])) {
            return $res;
        }

        $decoded = json_decode($res['text'], true);
        if (!$decoded) {
            return [
                'success' => false,
                'error' => 'Failed to decode structured JSON from Gemini.'
            ];
        }

        return [
            'success' => true,
            'seo_title' => $decoded['seo_title'] ?? '',
            'meta_description' => $decoded['meta_description'] ?? '',
            'keywords' => implode(', ', $decoded['keywords'] ?? []),
            'model' => $res['model']
        ];
    }

    /**
     * Call the Gemini Interactions API
     * 
     * @param string $inputText The user input text
     * @param array $options Additional options (e.g. previous_interaction_id, store, background, etc.)
     * @return array|null The API response array or null on failure
     */
    public function createInteraction(string $inputText, array $options = []): ?array
    {
        if (empty($this->apiKey)) {
            error_log("AIService Error: Gemini API Key is not set in configuration.");
            return null;
        }

        // Prepare the request payload in line with the Interactions API REST spec (input instead of contents)
        $payload = [
            'model' => $this->model,
            'input' => $inputText
        ];

        if (isset($options['store'])) {
            $payload['store'] = (bool)$options['store'];
        }
        if (isset($options['background'])) {
            $payload['background'] = (bool)$options['background'];
        }
        if (!empty($options['previous_interaction_id'])) {
            $payload['previous_interaction_id'] = $options['previous_interaction_id'];
        }
        if (!empty($options['config'])) {
            $payload['config'] = $options['config'];
        }

        $url = $this->baseUrl;
        $headers = [
            'Content-Type: application/json',
            'x-goog-api-key: ' . $this->apiKey
        ];

        $jsonPayload = json_encode($payload);

        if (!function_exists('curl_init')) {
            return $this->sendViaStreams($url, $headers, $jsonPayload);
        }

        return $this->sendViaCurl($url, $headers, $jsonPayload);
    }

    /**
     * Send HTTP POST request using native PHP streams
     */
    private function sendViaStreams(string $url, array $headers, string $jsonPayload): ?array
    {
        $headerString = "";
        foreach ($headers as $header) {
            $headerString .= $header . "\r\n";
        }

        $contextOptions = [
            'http' => [
                'header'  => $headerString,
                'method'  => 'POST',
                'content' => $jsonPayload,
                'timeout' => 30,
                'ignore_errors' => true
            ]
        ];

        $context  = stream_context_create($contextOptions);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            error_log("AIService HTTP Stream Error: " . ($error['message'] ?? 'Unknown error'));
            return null;
        }

        $httpCode = 0;
        $respHeaders = function_exists('http_get_last_response_headers')
            ? http_get_last_response_headers()
            : ($http_response_header ?? null);

        if ($respHeaders !== null && is_array($respHeaders) && isset($respHeaders[0])) {
            preg_match('{HTTP\/\S*\s(\d+)}', $respHeaders[0], $matches);
            $httpCode = isset($matches[1]) ? intval($matches[1]) : 0;
        }

        if ($httpCode !== 200) {
            error_log("AIService Stream HTTP Error {$httpCode}: " . $response);
            return null;
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("AIService Stream JSON Decode Error: " . json_last_error_msg());
            return null;
        }

        return $result;
    }

    /**
     * Send HTTP POST request using cURL extension
     */
    private function sendViaCurl(string $url, array $headers, string $jsonPayload): ?array
    {
        $httpResult = HttpClient::postJson($url, $jsonPayload, $headers, 30);

        if ($httpResult['error'] !== '') {
            error_log("AIService cURL Error: " . $httpResult['error']);
            return null;
        }

        if ($httpResult['status'] !== 200) {
            error_log("AIService cURL HTTP Error {$httpResult['status']}: " . $httpResult['body']);
            return null;
        }

        if (!is_array($httpResult['json'])) {
            error_log("AIService cURL JSON Decode Error: " . json_last_error_msg());
            return null;
        }

        return $httpResult['json'];
    }
}
