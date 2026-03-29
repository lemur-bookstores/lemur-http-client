<?php
/**
 * @package    LemurHttpClient
 * @category   Response
 * @author     [elkincp Chaverra]
 * @copyright  [2026] [lemur-bookstores]
 * @license    MIT
 * @since      1.0.0
 */

namespace LemurHttpClient;

/**
 * HTTP response object.
 *
 * Holds status, headers, body, cURL info, cookies, and redirect history.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class Response
{
    private int $status;
    private array $headers;
    private $body;
    private array $info;
    private array $cookies;
    private array $redirectHistory;

    /**
     * Response constructor.
     *
     * @param int    $status          HTTP status code.
     * @param array  $headers         Associative array of headers.
     * @param mixed  $body            Response body.
     * @param array  $info            cURL info array.
     * @param array  $cookies         Associative array of cookies.
     * @param array  $redirectHistory Array of redirect URLs.
     * @since 1.0.0
     */
    public function __construct(int $status, array $headers = [], $body = null, array $info = [], array $cookies = [], array $redirectHistory = [])
    {
        $this->status = $status;
        $this->headers = $this->normalizeHeaders($headers);
        $this->body = $body;
        $this->info = $info;
        $this->cookies = $cookies;
        $this->redirectHistory = $redirectHistory;
    }

    /**
     * Gets the HTTP status code.
     *
     * @return int HTTP status code.
     * @since 1.0.0
     */
    public function getStatus(): int { return $this->status; }

    /**
     * Gets the headers array.
     *
     * @return array Associative array of headers.
     * @since 1.0.0
     */
    public function getHeaders(): array { return $this->headers; }

    /**
     * Gets the response body.
     *
     * @return mixed Response body.
     * @since 1.0.0
     */
    public function getBody() { return $this->body; }

    /**
     * Gets the cURL info array.
     *
     * @return array cURL info array.
     * @since 1.0.0
     */
    public function getInfo(): array { return $this->info; }

    /**
     * Gets the cookies array.
     *
     * @return array Associative array of cookies.
     * @since 1.0.0
     */
    public function getCookies(): array { return $this->cookies; }

    /**
     * Gets the redirect history array.
     *
     * @return array Array of redirect URLs.
     * @since 1.0.0
     */
    public function getRedirectHistory(): array { return $this->redirectHistory; }

    /**
     * Returns true if the response is a successful 2xx status.
     *
     * @return bool True if status is 2xx.
     * @since 1.0.0
     */
    public function ok(): bool { return $this->status >= 200 && $this->status < 300; }

    /**
     * Returns true if the response is not successful (not 2xx).
     *
     * @return bool True if not 2xx.
     * @since 1.0.0
     */
    public function failed(): bool { return !$this->ok(); }

    /**
     * Returns true if the response is a client error (4xx).
     *
     * @return bool True if status is 4xx.
     * @since 1.0.0
     */
    public function clientError(): bool { return $this->status >= 400 && $this->status < 500; }

    /**
     * Returns true if the response is a server error (5xx).
     *
     * @return bool True if status is 5xx.
     * @since 1.0.0
     */
    public function serverError(): bool { return $this->status >= 500; }

    /**
     * Decodes the response body as JSON.
     *
     * @param bool $assoc Return associative array if true.
     * @return mixed      Decoded JSON data.
     * @since 1.0.0
     */
    public function json($assoc = true) {
        return json_decode($this->body, $assoc);
    }

    /**
     * Gets the size of the response body in bytes.
     *
     * @return int Size in bytes.
     * @since 1.0.0
     */
    public function size(): int {
        return strlen((string)$this->body);
    }

    /**
     * Gets a specific header value by name.
     *
     * @param string $name Header name.
     * @return mixed       Header value or null if not found.
     * @since 1.0.0
     */
    public function header(string $name) {
        $name = $this->normalizeHeaderName($name);
        return $this->headers[$name] ?? null;
    }

    /**
     * Gets a specific cookie value by name.
     *
     * @param string $name Cookie name.
     * @return mixed       Cookie value or null if not found.
     * @since 1.0.0
     */
    public function cookie(string $name) {
        return $this->cookies[$name] ?? null;
    }

    /**
     * Normalizes header names to standard format.
     *
     * @param array $headers Headers array.
     * @return array         Normalized headers array.
     * @since 1.0.0
     */
    private function normalizeHeaders(array $headers): array {
        $result = [];
        foreach ($headers as $k => $v) {
            $result[$this->normalizeHeaderName($k)] = $v;
        }
        return $result;
    }

    /**
     * Normalizes a header name to standard format (e.g. Content-Type).
     *
     * @param string $name Header name.
     * @return string      Normalized header name.
     * @since 1.0.0
     */
    private function normalizeHeaderName(string $name): string {
        return str_replace(' ', '-', ucwords(str_replace(['-', '_'], ' ', strtolower($name))));
    }
}
