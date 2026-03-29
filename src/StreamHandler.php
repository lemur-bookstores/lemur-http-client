<?php
namespace LemurHttpClient;

class StreamHandler
{
    public function handle($resource, callable $onData): void
    {
        while (!feof($resource)) {
            $chunk = fread($resource, 8192);
            if ($chunk === false) break;
            $onData($chunk);
        }
        fclose($resource);
    }
}
