<?php
namespace LemurHttpClient\Cache;

class CacheFactory
{
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
