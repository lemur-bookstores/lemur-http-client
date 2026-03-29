<?php
namespace LemurHttpClient;

/**
 * Value Object inmutable para representar una petición HTTP
 */
class Request
{
    private string $method;
    private string $url;
    private array $headers;
    private $body;
    private array $options;

    public function __construct(string $method, string $url, array $headers = [], $body = null, array $options = [])
    {
        $this->method = strtoupper($method);
        $this->url = $url;
        $this->headers = $this->normalizeHeaders($headers);
        $this->body = $body;
        $this->options = $options;
    }

    public function getMethod(): string { return $this->method; }
    public function getUrl(): string { return $this->url; }
    public function getHeaders(): array { return $this->headers; }
    public function getBody() { return $this->body; }
    public function getOptions(): array { return $this->options; }

    // Métodos withX() para inmutabilidad
    public function withMethod(string $method): self {
        $clone = clone $this;
        $clone->method = strtoupper($method);
        return $clone;
    }
    public function withUrl(string $url): self {
        $clone = clone $this;
        $clone->url = $url;
        return $clone;
    }
    public function withHeader(string $name, $value): self {
        $clone = clone $this;
        $clone->headers[$this->normalizeHeaderName($name)] = $value;
        return $clone;
    }
    public function withHeaders(array $headers): self {
        $clone = clone $this;
        foreach ($headers as $k => $v) {
            $clone->headers[$this->normalizeHeaderName($k)] = $v;
        }
        return $clone;
    }
    public function withBody($body): self {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }
    public function withOptions(array $options): self {
        $clone = clone $this;
        $clone->options = $options;
        return $clone;
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
