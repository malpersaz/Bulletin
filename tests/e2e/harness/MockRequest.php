<?php

namespace E2E\Harness;

class MockRequest
{
    private string $method;
    private string $uri;
    private array $getParams;
    private array $postParams;
    private array $headers;
    private ?string $rawBody;
    private array $cookies;

    public function __construct(
        string $method = 'GET',
        string $uri = '/',
        array $getParams = [],
        array $postParams = [],
        array $headers = [],
        ?string $rawBody = null,
        array $cookies = []
    ) {
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->getParams = $getParams;
        $this->postParams = $postParams;
        $this->headers = array_change_key_case($headers, CASE_LOWER);
        $this->rawBody = $rawBody;
        $this->cookies = $cookies;
    }

    public static function get(string $uri, array $params = [], array $headers = []): self
    {
        return new self('GET', $uri, $params, [], $headers);
    }

    public static function post(string $uri, array $data = [], array $headers = []): self
    {
        return new self('POST', $uri, [], $data, $headers);
    }

    public static function ajaxPost(string $uri, array $data = [], array $headers = []): self
    {
        $headers['x-requested-with'] = 'XMLHttpRequest';
        return new self('POST', $uri, [], $data, $headers);
    }

    public static function jsonPost(string $uri, array $payload = [], array $headers = []): self
    {
        $headers['content-type'] = 'application/json';
        $headers['x-requested-with'] = 'XMLHttpRequest';
        return new self('POST', $uri, [], $payload, $headers, json_encode($payload));
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->getParams[$key] ?? $default;
    }

    public function getPost(string $key, mixed $default = null): mixed
    {
        return $this->postParams[$key] ?? $default;
    }

    public function getAllPost(): array
    {
        return $this->postParams;
    }

    public function getAllGet(): array
    {
        return $this->getParams;
    }

    public function getHeader(string $name, ?string $default = null): ?string
    {
        $key = strtolower($name);
        return $this->headers[$key] ?? $default;
    }

    public function isAjax(): bool
    {
        return ($this->getHeader('x-requested-with') === 'XMLHttpRequest');
    }

    public function getBody(): ?string
    {
        return $this->rawBody;
    }

    public function getJson(): ?array
    {
        if ($this->rawBody === null) {
            return $this->postParams;
        }
        $decoded = json_decode($this->rawBody, true);
        return is_array($decoded) ? $decoded : null;
    }
}
