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
 * Bearer token authentication handler.
 *
 * Adds a Bearer Authorization header to the request.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class BearerAuth
{
    private string $token;

    /**
     * BearerAuth constructor.
     *
     * @param string $token Bearer token value.
     * @since 1.0.0
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Adds the Bearer Authorization header to the request.
     *
     * @param Request $request HTTP request object.
     * @return Request         Modified request with Authorization header.
     * @since 1.0.0
     */
    public function __invoke(Request $request): Request
    {
        return $request->withHeader('Authorization', 'Bearer ' . $this->token);
    }
}
