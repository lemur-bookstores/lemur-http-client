<?php
/**
 * @package    LemurHttpClient
 * @category   Request
 * @author     [elkincp Chaverra]
 * @copyright  [2026] [lemur-bookstores]
 * @license    MIT
 * @since      1.0.0
 */

namespace LemurHttpClient;

/**
 * Fluent builder for constructing Request objects.
 *
 * Handles merging headers, serializing body, and building query strings.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class RequestBuilder
{
    /**
     * Builds a Request object from method, URL, options, and defaults.
     *
     * Merges headers, serializes body (JSON or form), and appends query string.
     *
     * @param string $method   HTTP method (GET, POST, etc).
     * @param string $url      Request URL.
     * @param array  $options  Options: headers, body, json, form_params, query.
     * @param array  $defaults Default values (e.g. headers).
     * @return Request         Constructed Request object.
     * @since 1.0.0
     */
    public static function build(string $method, string $url, array $options = [], array $defaults = []): Request
    {
        $method = strtoupper($method);
        $headers = $defaults['headers'] ?? [];
        if (isset($options['headers'])) {
            $headers = array_merge($headers, $options['headers']);
        }
        $body = $options['body'] ?? null;
        // Serialize body if needed
        if (isset($options['json'])) {
            $body = json_encode($options['json']);
            $headers['Content-Type'] = 'application/json';
        } elseif (isset($options['form_params'])) {
            $body = http_build_query($options['form_params']);
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }
        // Build query string if present
        if (isset($options['query']) && is_array($options['query'])) {
            $query = http_build_query($options['query']);
            $url .= (strpos($url, '?') === false ? '?' : '&') . $query;
        }
        return new Request($method, $url, $headers, $body, $options);
    }
}
