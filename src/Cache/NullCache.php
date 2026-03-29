<?php
namespace LemurHttpClient\Cache;

class NullCache
{
    public function get($key) { return null; }
    public function set($key, $value, $ttl = null) { /* noop */ }
    public function delete($key) { /* noop */ }
}
