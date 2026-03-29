<?php
/**
 * @package    LemurHttpClient
 * @category   Adapter
 * @author     [elkincp Chaverra]
 * @copyright  [2026] [lemur-bookstores]
 * @license    MIT
 * @since      1.0.0
 */

namespace LemurHttpClient;

/**
 * Builds and configures a cURL handle from a Request object.
 *
 * Applies URL, method, headers, body, and custom CURLOPT_* options.
 * Does not execute the request — only prepares the handle.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class CurlHandleBuilder
{
    /**
     * Builds and configures a cURL handle from a Request object.
     *
     * Applies URL, method, headers, body, and custom CURLOPT_* options.
     * Does not execute the request — only prepares the handle.
     *
     * @param  Request     $request  The request to build the handle from.
     * @return \CurlHandle           Configured, ready-to-execute cURL handle.
     * @since  1.0.0
     */
    public function build(Request $request): \CurlHandle
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,           $request->getUrl());
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->getMethod());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER,         true); // Required for header parsing

        // Build headers array
        $headers = [];
        foreach ($request->getHeaders() as $k => $v) {
            $headers[] = "$k: $v";
        }
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        // Set body if present
        if ($request->getBody() !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request->getBody());
        }

        // Custom options (CURLOPT_* as string → constant)
        foreach ($request->getOptions() as $k => $v) {
            if (defined($k)) {
                curl_setopt($ch, constant($k), $v);
            }
        }

        return $ch;
    }

    /**
     * Builds a Response object from an executed cURL handle.
     *
     * Splits raw output into headers and body, parses status and info.
     *
     * @param  \CurlHandle $ch         The executed cURL handle.
     * @param  string      $rawOutput  The raw output from curl_exec.
     * @return Response                Parsed response object.
     * @since  1.0.0
     */
    public function parseResponse(\CurlHandle $ch, string $rawOutput): Response
    {
        $info       = curl_getinfo($ch);
        $headerSize = $info['header_size'] ?? 0;
        $rawHeaders = substr($rawOutput, 0, $headerSize);
        $rawBody    = substr($rawOutput, $headerSize);
        $status     = $info['http_code'] ?? 0;
        $headers    = $this->parseHeaders($rawHeaders);

        return new Response($status, $headers, $rawBody, $info);
    }

    /**
     * Parses the raw HTTP header block into an associative array.
     *
     * If a header is repeated (e.g. Set-Cookie), stores as array.
     *
     * @param  string $raw  Raw header string from cURL output.
     * @return array        Associative array of headers.
     * @since  1.0.0
     */
    private function parseHeaders(string $raw): array
    {
        $headers = [];
        foreach (explode("\r\n", $raw) as $line) {
            // Skip empty lines and HTTP status lines
            if (strpos($line, ':') !== false) {
                [$k, $v] = explode(':', $line, 2);
                $k = trim($k);
                $v = trim($v);
                if (isset($headers[$k])) {
                    if (is_array($headers[$k])) {
                        $headers[$k][] = $v;
                    } else {
                        $headers[$k] = [$headers[$k], $v];
                    }
                } else {
                    $headers[$k] = $v;
                }
            }
        }
        return $headers;
    }
}