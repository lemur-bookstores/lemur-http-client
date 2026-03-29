<?php
namespace LemurHttpClient\Auth;

use LemurHttpClient\Request;

class OAuth2
{
    private string $accessToken;
    public function __construct(string $accessToken)
    {
        $this->accessToken = $accessToken;
    }
    public function __invoke(Request $request): Request
    {
        return $request->withHeader('Authorization', 'Bearer ' . $this->accessToken);
    }
}
