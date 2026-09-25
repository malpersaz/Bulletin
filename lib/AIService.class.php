<?php

/**
 * Amed Framework
 * Class: AIService
 * File: AIService.class.php
 * Description: Google Gemini Interactions API REST Client (Stream/cURL Hybrid)
 */

class AIService
{
    private $apiKey;
    private $model;
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/interactions';

    public function __construct()
    {
        $site = Registry::has('site_config') ? Registry::get('site_config') : [];
        $this->apiKey = $site['gemini_api_key'] ?? '';
        $this->model = $site['gemini_model'] ?? 'gemini-2.5-flash';
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

        // Merge extra config if provided
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

        // Fallback to PHP Streams if curl is not installed/enabled
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
                'ignore_errors' => true // Capture response body even on non-200 HTTP codes
            ]
        ];

        $context  = stream_context_create($contextOptions);
        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            $error = error_get_last();
            error_log("AIService HTTP Stream Error: " . ($error['message'] ?? 'Unknown error'));
            return null;
        }

        // Extract HTTP Response Code from response headers
        $httpCode = 0;
        $headers = function_exists('http_get_last_response_headers')
            ? http_get_last_response_headers()
            : ($http_response_header ?? null);

        if ($headers !== null && is_array($headers) && isset($headers[0])) {
            preg_match('{HTTP\/\S*\s(\d+)}', $headers[0], $matches);
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
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch) !== 0) {
            $error_msg = curl_error($ch);
            error_log("AIService cURL Error: " . $error_msg);
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("AIService cURL HTTP Error {$httpCode}: " . $response);
            return null;
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("AIService cURL JSON Decode Error: " . json_last_error_msg());
            return null;
        }

        return $result;
    }
}
