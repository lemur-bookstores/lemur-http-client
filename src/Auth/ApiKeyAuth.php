<?php
namespace LemurHttpClient\Auth;

use LemurHttpClient\Request;

class ApiKeyAuth
{
    private string $key;
    private string $header;
    public function __construct(string $key, string $header = 'X-API-Key')
    {
        $this->key = $key;
        $this->header = $header;
    }
    public function __invoke(Request $request): Request
    {
        return $request->withHeader($this->header, $this->key);
    }
}
