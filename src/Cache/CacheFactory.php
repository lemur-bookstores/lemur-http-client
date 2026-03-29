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
 * Factory for creating cache implementations.
 *
 * Supports array, file, null, and PSR-16 cache adapters.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class CacheFactory
{
    /**
     * Creates a cache implementation by type.
     *
     * @param string $type    Cache type (array, file, null, psr16).
     * @param array  $options Optional options for cache constructor.
     * @return CacheInterface Created cache instance.
     * @throws \InvalidArgumentException If type or options are invalid.
     * @since 1.0.0
     */
    public static function create(string $type, array $options = []): CacheInterface
    {
        switch ($type) {
            case 'array':
                return new ArrayCache();
            case 'file':
                return new FileCache($options['dir'] ?? sys_get_temp_dir());
            case 'null':
                return new NullCache();
            case 'psr16':
                if (!isset($options['psr16'])) {
                    throw new \InvalidArgumentException('psr16 instance required');
                }
                return new Psr16CacheAdapter($options['psr16']);
            default:
                throw new \InvalidArgumentException('Unknown cache type: ' . $type);
        }
    }
}
