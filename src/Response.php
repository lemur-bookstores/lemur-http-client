<?php
namespace LemurHttpClient;

/**
 * Objeto de respuesta HTTP
 */
class Response
{
    private int $status;
    private array $headers;
    private $body;
    private array $info;
    private array $cookies;
    private array $redirectHistory;

    public function __construct(int $status, array $headers = [], $body = null, array $info = [], array $cookies = [], array $redirectHistory = [])
    {
        $this->status = $status;
        $this->headers = $this->normalizeHeaders($headers);
        $this->body = $body;
        $this->info = $info;
        $this->cookies = $cookies;
        $this->redirectHistory = $redirectHistory;
    }

    public function getStatus(): int { return $this->status; }
    public function getHeaders(): array { return $this->headers; }
    public function getBody() { return $this->body; }
    public function getInfo(): array { return $this->info; }
    public function getCookies(): array { return $this->cookies; }
    public function getRedirectHistory(): array { return $this->redirectHistory; }

    public function ok(): bool { return $this->status >= 200 && $this->status < 300; }
    public function failed(): bool { return !$this->ok(); }
    public function clientError(): bool { return $this->status >= 400 && $this->status < 500; }
    public function serverError(): bool { return $this->status >= 500; }

    public function json($assoc = true) {
        return json_decode($this->body, $assoc);
    }
    public function size(): int {
        return strlen((string)$this->body);
    }
    public function header(string $name) {
        $name = $this->normalizeHeaderName($name);
        return $this->headers[$name] ?? null;
    }
    public function cookie(string $name) {
        return $this->cookies[$name] ?? null;
    }

    private function normalizeHeaders(array $headers): array {
        $result = [];
        foreach ($headers as $k => $v) {
            $result[$this->normalizeHeaderName($k)] = $v;
        }
        return $result;
    }
    private function normalizeHeaderName(string $name): string {
        return str_replace(' ', '-', ucwords(str_replace(['-', '_'], ' ', strtolower($name))));
    }
}
