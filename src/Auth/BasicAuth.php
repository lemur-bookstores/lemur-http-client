<?php
namespace LemurHttpClient\Auth;

use LemurHttpClient\Request;

class BasicAuth
{
    private string $user;
    private string $pass;
    public function __construct(string $user, string $pass)
    {
        $this->user = $user;
        $this->pass = $pass;
    }
    public function __invoke(Request $request): Request
    {
        $auth = base64_encode($this->user . ':' . $this->pass);
        return $request->withHeader('Authorization', 'Basic ' . $auth);
    }
}
