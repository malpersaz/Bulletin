<?php

/**
 * Social Media Credentials Encryption Service
 * Encrypts and decrypts sensitive API keys, tokens, and secrets
 * using AES-256-CBC with HMAC-SHA-256 authenticated encryption.
 */
class SocialCrypto
{
    private const CIPHER_METHOD = 'aes-256-cbc';
    private const FALLBACK_SALT = 'AmedFramework_Social_Default_Salt_2026';

    /**
     * Resolves the 256-bit encryption key from environment or config.
     *
     * @return string 32-byte binary key
     */
    private static function getKey(): string
    {
        $rawKey = getenv('AMED_SOCIAL_SECRET_KEY');
        if (empty($rawKey) && class_exists('Registry') && Registry::has('site_config')) {
            $site = Registry::get('site_config');
            if (!empty($site['social_secret_key'])) {
                $rawKey = (string) $site['social_secret_key'];
            }
        }

        if (empty($rawKey)) {
            // Deterministic server-bound fallback key
            $seed = self::FALLBACK_SALT;
            if (defined('root_dir')) {
                $seed .= root_dir;
            }
            if (class_exists('Registry') && Registry::has('db_config')) {
                $db = Registry::get('db_config');
                $seed .= serialize($db);
            }
            $rawKey = hash('sha256', $seed);
        }

        return hash('sha256', $rawKey, true);
    }

    /**
     * Encrypt a plaintext string.
     *
     * @param string $plainText
     * @return string Base64 encoded payload containing iv, ciphertext, and hmac
     */
    public static function encrypt(string $plainText): string
    {
        $key = self::getKey();
        $ivLength = openssl_cipher_iv_length(self::CIPHER_METHOD);
        $iv = openssl_random_pseudo_bytes($ivLength);

        $cipherRaw = openssl_encrypt($plainText, self::CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv);
        if ($cipherRaw === false) {
            throw new \RuntimeException('SocialCrypto: Encryption failed');
        }

        $hmac = hash_hmac('sha256', $iv . $cipherRaw, $key, true);

        return base64_encode(json_encode([
            'v' => 1,
            'iv' => base64_encode($iv),
            'c' => base64_encode($cipherRaw),
            'h' => base64_encode($hmac)
        ]));
    }

    /**
     * Decrypt an encrypted payload string.
     *
     * @param string $cipherText
     * @return string|null Decrypted plaintext, or null on decryption failure
     */
    public static function decrypt(string $cipherText): ?string
    {
        if (empty($cipherText)) {
            return null;
        }

        $decodedJson = base64_decode($cipherText, true);
        if ($decodedJson === false) {
            return null;
        }

        $data = json_decode($decodedJson, true);
        if (!is_array($data) || !isset($data['iv'], $data['c'], $data['h'])) {
            return null;
        }

        $iv = base64_decode((string) $data['iv'], true);
        $cipherRaw = base64_decode((string) $data['c'], true);
        $expectedHmac = base64_decode((string) $data['h'], true);

        if ($iv === false || $cipherRaw === false || $expectedHmac === false) {
            return null;
        }

        $key = self::getKey();
        $calculatedHmac = hash_hmac('sha256', $iv . $cipherRaw, $key, true);

        if (!hash_equals($calculatedHmac, $expectedHmac)) {
            // HMAC authentication failed (corrupted or tampered)
            return null;
        }

        $plain = openssl_decrypt($cipherRaw, self::CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv);
        return ($plain !== false) ? $plain : null;
    }

    /**
     * Encrypt an array payload to ciphertext.
     *
     * @param array $payload
     * @return string
     */
    public static function encryptPayload(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return self::encrypt($json);
    }

    /**
     * Decrypt ciphertext back to an array payload.
     *
     * @param string $cipherText
     * @return array|null
     */
    public static function decryptPayload(string $cipherText): ?array
    {
        $plain = self::decrypt($cipherText);
        if ($plain === null) {
            return null;
        }

        $decoded = json_decode($plain, true);
        return is_array($decoded) ? $decoded : null;
    }
}
