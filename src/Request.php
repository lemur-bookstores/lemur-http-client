<?php
/**
 * @package    LemurHttpClient
 * @category   Request
 * @author     [elkincp Chaverra]
 * @copyright  [2026] [lemur-bookstores]
 * @license    MIT
 * @since      1.0.0
 */

namespace LemurHttpClient;

/**
 * Immutable Value Object representing an HTTP request.
 *
 * Holds method, URL, headers, body, and cURL options. Provides fluent withX() methods for immutability.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class Request
{
    private string $method;
    private string $url;
    private array $headers;
    private $body;
    private array $options;

    /**
     * Request constructor.
     *
     * @param string $method   HTTP method (GET, POST, etc).
     * @param string $url      Request URL.
     * @param array  $headers  Associative array of headers.
     * @param mixed  $body     Request body (string, array, or resource).
     * @param array  $options  Custom cURL options.
     * @since 1.0.0
     */
    public function __construct(string $method, string $url, array $headers = [], $body = null, array $options = [])
    {
        $this->method = strtoupper($method);
        $this->url = $url;
        $this->headers = $this->normalizeHeaders($headers);
        $this->body = $body;
        $this->options = $options;
    }

    /**
     * Gets the HTTP method.
     *
     * @return string HTTP method.
     * @since 1.0.0
     */
    public function getMethod(): string { return $this->method; }

    /**
     * Gets the request URL.
     *
     * @return string Request URL.
     * @since 1.0.0
     */
    public function getUrl(): string { return $this->url; }

    /**
     * Gets the headers array.
     *
     * @return array Associative array of headers.
     * @since 1.0.0
     */
    public function getHeaders(): array { return $this->headers; }

    /**
     * Gets the request body.
     *
     * @return mixed Request body.
     * @since 1.0.0
     */
    public function getBody() { return $this->body; }

    /**
     * Gets the custom cURL options.
     *
     * @return array Associative array of cURL options.
     * @since 1.0.0
     */
    public function getOptions(): array { return $this->options; }

    // Fluent withX() methods for immutability

    /**
     * Returns a clone with a new HTTP method.
     *
     * @param string $method HTTP method.
     * @return self          New Request instance.
     * @since 1.0.0
     */
    public function withMethod(string $method): self {
        $clone = clone $this;
        $clone->method = strtoupper($method);
        return $clone;
    }

    /**
     * Returns a clone with a new URL.
     *
     * @param string $url New URL.
     * @return self       New Request instance.
     * @since 1.0.0
     */
    public function withUrl(string $url): self {
        $clone = clone $this;
        $clone->url = $url;
        return $clone;
    }

    /**
     * Returns a clone with a new header.
     *
     * @param string $name  Header name.
     * @param mixed  $value Header value.
     * @return self         New Request instance.
     * @since 1.0.0
     */
    public function withHeader(string $name, $value): self {
        $clone = clone $this;
        $clone->headers[$this->normalizeHeaderName($name)] = $value;
        return $clone;
    }

    /**
     * Returns a clone with new headers merged.
     *
     * @param array $headers Headers to merge.
     * @return self          New Request instance.
     * @since 1.0.0
     */
    public function withHeaders(array $headers): self {
        $clone = clone $this;
        foreach ($headers as $k => $v) {
            $clone->headers[$this->normalizeHeaderName($k)] = $v;
        }
        return $clone;
    }

    /**
     * Returns a clone with a new body.
     *
     * @param mixed $body New body.
     * @return self       New Request instance.
     * @since 1.0.0
     */
    public function withBody($body): self {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    /**
     * Returns a clone with new cURL options.
     *
     * @param array $options New cURL options.
     * @return self          New Request instance.
     * @since 1.0.0
     */
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
