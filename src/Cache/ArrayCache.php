<?php
namespace LemurHttpClient\Cache;

class ArrayCache
{
    private array $data = [];
    public function get($key) { return $this->data[$key] ?? null; }
    public function set($key, $value, $ttl = null) { $this->data[$key] = $value; }
    public function delete($key) { unset($this->data[$key]); }
}
