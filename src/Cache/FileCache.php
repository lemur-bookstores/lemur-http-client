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
 * File-based cache implementation.
 *
 * Stores cache entries as serialized files in a directory.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class FileCache implements CacheInterface
{
    private string $dir;

    /**
     * FileCache constructor.
     *
     * @param string $dir Directory for cache files.
     * @since 1.0.0
     */
    public function __construct(string $dir)
    {
        $this->dir = rtrim($dir, DIRECTORY_SEPARATOR);
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0777, true);
        }
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
        $file = $this->dir . DIRECTORY_SEPARATOR . md5($key);
        if (!file_exists($file)) return null;
        return unserialize(file_get_contents($file));
    }

    /**
     * Sets a value in the cache.
     *
     * @param mixed    $key   Cache key.
     * @param mixed    $value Value to store.
     * @param int|null $ttl   Optional time-to-live (ignored).
     * @return void
     * @since 1.0.0
     */
    public function set($key, $value, $ttl = null)
    {
        $file = $this->dir . DIRECTORY_SEPARATOR . md5($key);
        file_put_contents($file, serialize($value));
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
        $file = $this->dir . DIRECTORY_SEPARATOR . md5($key);
        if (file_exists($file)) unlink($file);
    }
}
