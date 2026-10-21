<?php

/**
 * Amed Framework
 * Class: HttpClient
 * File: HttpClient.class.php
 * Description: Lightweight PHP 8.5 cURL & stream HTTP transport helper
 */

class HttpClient
{
    /**
     * Send an HTTP POST request with a JSON payload.
     *
     * @param string $url
     * @param array|string $payload
     * @param array $headers
     * @param int $timeout
     * @return array{status: int, body: string, json: array|null, error: string}
     */
    public static function postJson(string $url, array|string $payload, array $headers = [], int $timeout = 30): array
    {
        if (!preg_match('/^https?:\/\//i', $url)) {
            return [
                'status' => 0,
                'body' => '',
                'json' => null,
                'error' => 'Disallowed URL scheme',
            ];
        }

        $jsonBody = is_string($payload) ? $payload : (string) json_encode($payload);
        $defaultHeaders = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        $mergedHeaders = array_merge($defaultHeaders, $headers);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_HTTPHEADER => $mergedHeaders,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);

        if ($response === false || $curlErr !== '') {
            return [
                'status' => $httpCode,
                'body' => '',
                'json' => null,
                'error' => $curlErr !== '' ? $curlErr : 'cURL execution failed',
            ];
        }

        $decoded = json_decode((string) $response, true);
        return [
            'status' => $httpCode,
            'body' => (string) $response,
            'json' => is_array($decoded) ? $decoded : null,
            'error' => '',
        ];
    }

    /**
     * Send an HTTP POST request with URL-encoded form fields.
     *
     * @param string $url
     * @param array $fields
     * @param array $headers
     * @param int $timeout
     * @return array{status: int, body: string, json: array|null, error: string}
     */
    public static function postForm(string $url, array $fields, array $headers = [], int $timeout = 15): array
    {
        if (!preg_match('/^https?:\/\//i', $url)) {
            return [
                'status' => 0,
                'body' => '',
                'json' => null,
                'error' => 'Disallowed URL scheme',
            ];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);

        if ($response === false || $curlErr !== '') {
            return [
                'status' => $httpCode,
                'body' => '',
                'json' => null,
                'error' => $curlErr !== '' ? $curlErr : 'cURL execution failed',
            ];
        }

        $decoded = json_decode((string) $response, true);
        return [
            'status' => $httpCode,
            'body' => (string) $response,
            'json' => is_array($decoded) ? $decoded : null,
            'error' => '',
        ];
    }
}
