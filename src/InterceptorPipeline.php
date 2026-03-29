<?php
/**
 * @package    LemurHttpClient
 * @category   Support
 * @author     [elkincp Chaverra]
 * @copyright  [2026] [lemur-bookstores]
 * @license    MIT
 * @since      1.0.0
 */

namespace LemurHttpClient;

/**
 * Interceptor pipeline (middleware) for requests and responses.
 *
 * Allows adding, removing, and executing request/response interceptors.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class InterceptorPipeline
{
    private array $requestInterceptors = [];
    private array $responseInterceptors = [];

    /**
     * Adds a request interceptor to the pipeline.
     *
     * @param callable $fn Interceptor function.
     * @return string      Unique ID for the interceptor.
     * @since 1.0.0
     */
    public function addRequestInterceptor(callable $fn): string
    {
        $id = uniqid('req_', true);
        $this->requestInterceptors[$id] = $fn;
        return $id;
    }

    /**
     * Adds a response interceptor to the pipeline.
     *
     * @param callable $fn Interceptor function.
     * @return string      Unique ID for the interceptor.
     * @since 1.0.0
     */
    public function addResponseInterceptor(callable $fn): string
    {
        $id = uniqid('res_', true);
        $this->responseInterceptors[$id] = $fn;
        return $id;
    }

    /**
     * Removes an interceptor by its unique ID.
     *
     * @param string $id Interceptor ID.
     * @return void
     * @since 1.0.0
     */
    public function removeInterceptor(string $id): void
    {
        unset($this->requestInterceptors[$id], $this->responseInterceptors[$id]);
    }

    /**
     * Passes a Request through all request interceptors.
     *
     * @param Request $request The request to process.
     * @return Request         The processed request.
     * @since 1.0.0
     */
    public function handleRequest(Request $request): Request
    {
        foreach ($this->requestInterceptors as $fn) {
            $request = $fn($request);
        }
        return $request;
    }

    /**
     * Passes a Response through all response interceptors.
     *
     * @param Response $response The response to process.
     * @return Response          The processed response.
     * @since 1.0.0
     */
    public function handleResponse(Response $response): Response
    {
        foreach ($this->responseInterceptors as $fn) {
            $response = $fn($response);
        }
        return $response;
    }
}
