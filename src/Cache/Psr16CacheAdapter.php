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

use Psr\SimpleCache\CacheInterface as Psr16CacheInterface;

/**
 * PSR-16 cache adapter implementation.
 *
 * Wraps a PSR-16 cache instance for use with LemurHttpClient.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class Psr16CacheAdapter implements CacheInterface
{
    private Psr16CacheInterface $psr16;

    /**
     * Psr16CacheAdapter constructor.
     *
     * @param Psr16CacheInterface $psr16 PSR-16 cache instance.
     * @since 1.0.0
     */
    public function __construct(Psr16CacheInterface $psr16)
    {
        $this->psr16 = $psr16;
    }

    /**
     * Gets a value from the cache by key.
     *
     * @param mixed $key Cache key.
     * @return mixed     Cached value or null if not found.
     * @since 1.0.0
     */
    public function get($key)
    {
        return $this->psr16->get($key);
    }

    /**
     * Sets a value in the cache.
     *
     * @param mixed    $key   Cache key.
     * @param mixed    $value Value to store.
     * @param int|null $ttl   Optional time-to-live.
     * @return void
     * @since 1.0.0
     */
    public function set($key, $value, $ttl = null)
    {
        $this->psr16->set($key, $value, $ttl);
    }

    /**
     * Deletes a value from the cache by key.
     *
     * @param mixed $key Cache key.
     * @return void
     * @since 1.0.0
     */
    public function delete($key)
    {
        $this->psr16->delete($key);
    }
}
