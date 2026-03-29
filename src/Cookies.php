<?php
namespace LemurHttpClient;

class Cookies
{
    private array $cookies = [];

    public function set(string $name, string $value): void
    {
        $this->cookies[$name] = $value;
    }
    public function get(string $name): ?string
    {
        return $this->cookies[$name] ?? null;
    }
    public function all(): array
    {
        return $this->cookies;
    }
    public function toHeader(): string
    {
        $pairs = [];
        foreach ($this->cookies as $k => $v) {
            $pairs[] = "$k=$v";
        }
        return implode('; ', $pairs);
    }
}
