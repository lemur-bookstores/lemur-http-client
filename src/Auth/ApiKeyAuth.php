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
 * API key authentication handler.
 *
 * Adds an API key to a specified header in the request.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class ApiKeyAuth
{
    private string $key;
    private string $header;

    /**
     * ApiKeyAuth constructor.
     *
     * @param string $key    API key value.
     * @param string $header Header name (default: X-API-Key).
     * @since 1.0.0
     */
    public function __construct(string $key, string $header = 'X-API-Key')
    {
        $this->key = $key;
        $this->header = $header;
    }

    /**
     * Adds the API key to the request header.
     *
     * @param Request $request HTTP request object.
     * @return Request         Modified request with API key header.
     * @since 1.0.0
     */
    public function __invoke(Request $request): Request
    {
        return $request->withHeader($this->header, $this->key);
    }
}
