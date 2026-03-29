<?php
namespace LemurHttpClient\Cache;

class FileCache implements CacheInterface
{
    private string $dir;
    public function __construct(string $dir)
    {
        $this->dir = rtrim($dir, DIRECTORY_SEPARATOR);
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0777, true);
        }
    }
    public function get($key)
    {
        $file = $this->dir . DIRECTORY_SEPARATOR . md5($key);
        if (!file_exists($file)) return null;
        return unserialize(file_get_contents($file));
    }
    public function set($key, $value, $ttl = null)
    {
        $file = $this->dir . DIRECTORY_SEPARATOR . md5($key);
        file_put_contents($file, serialize($value));
    }
    public function delete($key)
    {
        $file = $this->dir . DIRECTORY_SEPARATOR . md5($key);
        if (file_exists($file)) unlink($file);
    }
}
