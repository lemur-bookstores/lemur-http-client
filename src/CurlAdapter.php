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
 * Adapter for sending HTTP requests using cURL.
 *
 * Uses CurlHandleBuilder to build and parse cURL requests and responses.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class CurlAdapter
{
    private CurlHandleBuilder $builder;

    /**
     * CurlAdapter constructor.
     *
     * Allows dependency injection for testing; creates a default builder if not provided.
     *
     * @param CurlHandleBuilder|null $builder Optional handle builder for testing or customization.
     * @since 1.0.0
     */
    public function __construct(CurlHandleBuilder $builder = null)
    {
        $this->builder = $builder ?? new CurlHandleBuilder();
    }

    /**
     * Sends an HTTP request and returns the response.
     *
     * Builds the cURL handle, executes the request, parses the response, and closes the handle.
     * Throws RuntimeException on cURL error.
     *
     * @param  Request  $request  The HTTP request to send.
     * @return Response           The HTTP response object.
     * @throws \RuntimeException  If a cURL error occurs during execution.
     * @since  1.0.0
     */
    public function send(Request $request): Response
    {
        $ch     = $this->builder->build($request);
        $output = curl_exec($ch);

        if ($output === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("cURL error: $error");
        }

        $response = $this->builder->parseResponse($ch, $output);
        curl_close($ch);

        return $response;
    }
}