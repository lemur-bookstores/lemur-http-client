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
 * OAuth2 authentication handler.
 *
 * Adds an OAuth2 Bearer Authorization header to the request.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class OAuth2
{
    private string $accessToken;

    /**
     * OAuth2 constructor.
     *
     * @param string $accessToken OAuth2 access token.
     * @since 1.0.0
     */
    public function __construct(string $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * Adds the OAuth2 Bearer Authorization header to the request.
     *
     * @param Request $request HTTP request object.
     * @return Request         Modified request with Authorization header.
     * @since 1.0.0
     */
    public function __invoke(Request $request): Request
    {
        return $request->withHeader('Authorization', 'Bearer ' . $this->accessToken);
    }
}
