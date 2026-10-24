<?php

/**
 * Interface GeminiTransport
 *
 * Decoupled HTTP transport interface for Gemini API communication.
 * Enables deterministic testing via mock/fake transports and local servers.
 */
interface GeminiTransport
{
    /**
     * Sends an HTTP request to Gemini API.
     *
     * @param string $url Target endpoint URL
     * @param array $headers List of HTTP header strings (e.g. ['Content-Type: application/json'])
     * @param array $payload JSON payload data
     * @param int $timeout cURL timeout in seconds
     * @param callable|null $onTick Optional progress callback: fn($handle, int $downloadTotal, int $downloadNow, int $uploadTotal, int $uploadNow): ?int
     * @return array [
     *     'http_code' => int,
     *     'response' => string,
     *     'curl_errno' => int,
     *     'curl_error' => string
     * ]
     */
    public function send(string $url, array $headers, array $payload, int $timeout, ?callable $onTick = null): array;
}

/**
 * Default production cURL-based Gemini transport with real-time heartbeat and early abort support.
 */
class CurlGeminiTransport implements GeminiTransport
{
    public function send(string $url, array $headers, array $payload, int $timeout, ?callable $onTick = null): array
    {
        if (class_exists('AgentRunner') && method_exists('AgentRunner', 'normalizePayloadForGemini')) {
            $payload = AgentRunner::normalizePayloadForGemini($payload);
        }

        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($handle, CURLOPT_TIMEOUT, max(1, $timeout));

        if ($onTick !== null) {
            curl_setopt($handle, CURLOPT_NOPROGRESS, false);
            curl_setopt($handle, CURLOPT_XFERINFOFUNCTION, function ($ch, int $downloadTotal, int $downloadNow, int $uploadTotal, int $uploadNow) use ($onTick) {
                $abort = $onTick($ch, $downloadTotal, $downloadNow, $uploadTotal, $uploadNow);
                if ($abort !== null && $abort !== 0 && $abort !== false) {
                    return 1; // Aborts cURL with CURLE_ABORTED_BY_CALLBACK
                }
                return 0;
            });
        }

        $response = curl_exec($handle);
        $httpCode = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $curlErrorCode = (int) curl_errno($handle);
        $curlErrorMessage = (string) curl_error($handle);
        curl_close($handle);

        return [
            'http_code' => $httpCode,
            'response' => ($response !== false) ? (string) $response : '',
            'curl_errno' => $curlErrorCode,
            'curl_error' => $curlErrorMessage,
        ];
    }
}
