<?php
namespace LemurHttpClient;

/**
 * Builder fluido para construir objetos Request
 */
class RequestBuilder
{
    public static function build(string $method, string $url, array $options = [], array $defaults = []): Request
    {
        $method = strtoupper($method);
        $headers = $defaults['headers'] ?? [];
        if (isset($options['headers'])) {
            $headers = array_merge($headers, $options['headers']);
        }
        $body = $options['body'] ?? null;
        // Serialización de body
        if (isset($options['json'])) {
            $body = json_encode($options['json']);
            $headers['Content-Type'] = 'application/json';
        } elseif (isset($options['form_params'])) {
            $body = http_build_query($options['form_params']);
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }
        // Construcción de query string
        if (isset($options['query']) && is_array($options['query'])) {
            $query = http_build_query($options['query']);
            $url .= (strpos($url, '?') === false ? '?' : '&') . $query;
        }
        return new Request($method, $url, $headers, $body, $options);
    }
}
