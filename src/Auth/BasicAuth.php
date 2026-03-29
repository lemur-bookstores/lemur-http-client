<?php
/**
 * @package    LemurHttpClient
 * @category   Auth
 * @author     [elkincp Chaverra]
 * @copyright  [2026] [lemur-bookstores]
 * @license    MIT
 * @since      1.0.0
 */

namespace LemurHttpClient\Auth;

use LemurHttpClient\Request;

/**
 * Basic authentication handler.
 *
 * Adds a Basic Authorization header to the request.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class BasicAuth
{
    private string $user;
    private string $pass;

    /**
     * BasicAuth constructor.
     *
     * @param string $user Username.
     * @param string $pass Password.
     * @since 1.0.0
     */
    public function __construct(string $user, string $pass)
    {
        $this->user = $user;
        $this->pass = $pass;
    }

    /**
     * Adds the Basic Authorization header to the request.
     *
     * @param Request $request HTTP request object.
     * @return Request         Modified request with Authorization header.
     * @since 1.0.0
     */
    public function __invoke(Request $request): Request
    {
        $auth = base64_encode($this->user . ':' . $this->pass);
        return $request->withHeader('Authorization', 'Basic ' . $auth);
    }
}
