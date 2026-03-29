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
 * In-memory array cache implementation.
 *
 * Stores cache entries in a local array for the duration of the process.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class ArrayCache
{
    private array $data = [];

    /**
     * Gets a value from the cache by key.
     *
     * @param mixed $key Cache key.
     * @return mixed     Cached value or null if not found.
     * @since 1.0.0
     */
    public function get($key) { return $this->data[$key] ?? null; }

    /**
     * Sets a value in the cache.
     *
     * @param mixed $key   Cache key.
     * @param mixed $value Value to store.
     * @param int|null $ttl Optional time-to-live (ignored).
     * @return void
     * @since 1.0.0
     */
    public function set($key, $value, $ttl = null) { $this->data[$key] = $value; }

    /**
     * Deletes a value from the cache by key.
     *
     * @param mixed $key Cache key.
     * @return void
     * @since 1.0.0
     */
    public function delete($key) { unset($this->data[$key]); }
}
