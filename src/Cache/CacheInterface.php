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
 * Interface for cache implementations.
 *
 * Defines basic get, set, and delete operations.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
interface CacheInterface
{
    /**
     * Gets a value from the cache by key.
     *
     * @param mixed $key Cache key.
     * @return mixed     Cached value or null if not found.
     * @since 1.0.0
     */
    public function get($key);

    /**
     * Sets a value in the cache.
     *
     * @param mixed    $key   Cache key.
     * @param mixed    $value Value to store.
     * @param int|null $ttl   Optional time-to-live.
     * @return void
     * @since 1.0.0
     */
    public function set($key, $value, $ttl = null);

    /**
     * Deletes a value from the cache by key.
     *
     * @param mixed $key Cache key.
     * @return void
     * @since 1.0.0
     */
    public function delete($key);
}
