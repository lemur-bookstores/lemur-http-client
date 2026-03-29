<?php
namespace LemurHttpClient\Auth;

use LemurHttpClient\Request;

class BearerAuth
{
    private string $token;
    public function __construct(string $token)
    {
        $this->token = $token;
    }
    public function __invoke(Request $request): Request
    {
        return $request->withHeader('Authorization', 'Bearer ' . $this->token);
    }
}
