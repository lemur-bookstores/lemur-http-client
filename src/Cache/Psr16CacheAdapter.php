<?php
namespace LemurHttpClient\Cache;

use Psr\SimpleCache\CacheInterface as Psr16CacheInterface;

class Psr16CacheAdapter implements CacheInterface
{
    private Psr16CacheInterface $psr16;
    public function __construct(Psr16CacheInterface $psr16)
    {
        $this->psr16 = $psr16;
    }
    public function get($key)
    {
        return $this->psr16->get($key);
    }
    public function set($key, $value, $ttl = null)
    {
        $this->psr16->set($key, $value, $ttl);
    }
    public function delete($key)
    {
        $this->psr16->delete($key);
    }
}
