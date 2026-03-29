<?php
/**
 * @package    LemurHttpClient
 * @category   Support
 * @author     [elkincp Chaverra]
 * @copyright  [2026] [lemur-bookstores]
 * @license    MIT
 * @since      1.0.0
 */

namespace LemurHttpClient;

/**
 * Manages HTTP cookies for requests and responses.
 *
 * Provides methods to set, get, list, and serialize cookies for headers.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class Cookies
{
    private array $cookies = [];

    /**
     * Sets a cookie value by name.
     *
     * @param string $name  Cookie name.
     * @param string $value Cookie value.
     * @return void
     * @since 1.0.0
     */
    public function set(string $name, string $value): void
    {
        $this->cookies[$name] = $value;
    }

    /**
     * Gets a cookie value by name.
     *
     * @param string $name Cookie name.
     * @return string|null  Cookie value or null if not set.
     * @since 1.0.0
     */
    public function get(string $name): ?string
    {
        return $this->cookies[$name] ?? null;
    }

    /**
     * Returns all cookies as an associative array.
     *
     * @return array All cookies.
     * @since 1.0.0
     */
    public function all(): array
    {
        return $this->cookies;
    }

    /**
     * Serializes cookies for use in a Cookie header.
     *
     * @return string Cookie header string.
     * @since 1.0.0
     */
    public function toHeader(): string
    {
        $pairs = [];
        foreach ($this->cookies as $k => $v) {
            $pairs[] = "$k=$v";
        }
        return implode('; ', $pairs);
    }
}
