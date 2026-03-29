<?php
/**
 * @package    LemurHttpClient
 * @category   Cache
 * @author     [elkincp Chaverra]
 * @copyright  [2026] [lemur-bookstores]
 * @license    MIT
 * @since      1.0.0
 */

namespace LemurHttpClient\Cache;

/**
 * Null cache implementation (no-op).
 *
 * Discards all cache operations; always returns null.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class NullCache
{
    /**
     * Gets a value from the cache by key (always null).
     *
     * @param mixed $key Cache key.
     * @return null Always null.
     * @since 1.0.0
     */
    public function get($key) { return null; }

    /**
     * Sets a value in the cache (no-op).
     *
     * @param mixed    $key   Cache key.
     * @param mixed    $value Value to store.
     * @param int|null $ttl   Optional time-to-live.
     * @return void
     * @since 1.0.0
     */
    public function set($key, $value, $ttl = null) { /* noop */ }

    /**
     * Deletes a value from the cache (no-op).
     *
     * @param mixed $key Cache key.
     * @return void
     * @since 1.0.0
     */
    public function delete($key) { /* noop */ }
}
