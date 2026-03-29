<?php
namespace LemurHttpClient;

/**
 * Pipeline de interceptors (middleware) para requests y responses
 */
class InterceptorPipeline
{
    private array $requestInterceptors = [];
    private array $responseInterceptors = [];

    public function addRequestInterceptor(callable $fn): string
    {
        $id = uniqid('req_', true);
        $this->requestInterceptors[$id] = $fn;
        return $id;
    }
    public function addResponseInterceptor(callable $fn): string
    {
        $id = uniqid('res_', true);
        $this->responseInterceptors[$id] = $fn;
        return $id;
    }
    public function removeInterceptor(string $id): void
    {
        unset($this->requestInterceptors[$id], $this->responseInterceptors[$id]);
    }
    public function handleRequest(Request $request): Request
    {
        foreach ($this->requestInterceptors as $fn) {
            $request = $fn($request);
        }
        return $request;
    }
    public function handleResponse(Response $response): Response
    {
        foreach ($this->responseInterceptors as $fn) {
            $response = $fn($response);
        }
        return $response;
    }
}
