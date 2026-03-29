<?php
namespace LemurHttpClient;

use LemurHttpClient\Cache\CacheFactory;
use LemurHttpClient\Cache\CacheInterface;
use LemurHttpClient\InterceptorPipeline;
use LemurHttpClient\RetryHandler;
use LemurHttpClient\Cookies;
use LemurHttpClient\MultipartBuilder;
use LemurHttpClient\StreamHandler;

/**
 * Fachada principal del framework LemurHttpClient
 */
class HttpClient
{
    private array $config;
    private CurlAdapter $adapter;
    private CurlMultiAdapter $multiAdapter;
    private ?InterceptorPipeline $pipeline = null;
    private ?RetryHandler $retryHandler = null;
    private $auth = null;
    private ?CacheInterface $cache = null;
    private ?Cookies $cookies = null;
    private ?StreamHandler $streamHandler = null;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->adapter = new CurlAdapter();
        $this->multiAdapter = new CurlMultiAdapter();
        $this->pipeline = $config['pipeline'] ?? new InterceptorPipeline();
        $this->retryHandler = $config['retry'] ?? new RetryHandler();
        $this->auth = $config['auth'] ?? null;
        $this->cache = $config['cache'] ?? null;
        $this->cookies = $config['cookies'] ?? new Cookies();
        $this->streamHandler = $config['stream'] ?? new StreamHandler();
    }

    public function setAuth($auth): void { $this->auth = $auth; }
    public function setCache(CacheInterface $cache): void { $this->cache = $cache; }
    public function setRetryHandler(RetryHandler $retry): void { $this->retryHandler = $retry; }
    public function setPipeline(InterceptorPipeline $pipeline): void { $this->pipeline = $pipeline; }
    public function setCookies(Cookies $cookies): void { $this->cookies = $cookies; }
    public function setStreamHandler(StreamHandler $stream): void { $this->streamHandler = $stream; }

    public function get(string $url, array $options = []): Response
    { return $this->request('GET', $url, $options); }
    public function post(string $url, array $options = []): Response
    { return $this->request('POST', $url, $options); }
    public function put(string $url, array $options = []): Response
    { return $this->request('PUT', $url, $options); }
    public function patch(string $url, array $options = []): Response
    { return $this->request('PATCH', $url, $options); }
    public function delete(string $url, array $options = []): Response
    { return $this->request('DELETE', $url, $options); }
    public function head(string $url, array $options = []): Response
    { return $this->request('HEAD', $url, $options); }
    public function options(string $url, array $options = []): Response
    { return $this->request('OPTIONS', $url, $options); }

    public function request(string $method, string $url, array $options = []): Response
    {
        $request = RequestBuilder::build($method, $url, $options, $this->config);
        // Auth
        if ($this->auth) {
            $request = ($this->auth)($request);
        }
        // Cookies
        if ($this->cookies && $this->cookies->all()) {
            $request = $request->withHeader('Cookie', $this->cookies->toHeader());
        }
        // Interceptors (request)
        if ($this->pipeline) {
            $request = $this->pipeline->handleRequest($request);
        }
        // Retry + Cache
        $send = function() use ($request) {
            // Cache (solo GET)
            if ($this->cache && $request->getMethod() === 'GET') {
                $cacheKey = md5($request->getUrl() . serialize($request->getHeaders()));
                $cached = $this->cache->get($cacheKey);
                if ($cached) return $cached;
            }
            $response = $this->adapter->send($request);
            if ($this->cache && $request->getMethod() === 'GET') {
                $this->cache->set($cacheKey, $response);
            }
            return $response;
        };
        $response = $this->retryHandler ? $this->retryHandler->handle($send) : $send();
        // Interceptors (response)
        if ($this->pipeline) {
            $response = $this->pipeline->handleResponse($response);
        }
        return $response;
    }

    public function all(array $requests): array
    {
        $responses = $this->multiAdapter->sendAll($requests);
        foreach ($responses as $response) {
            if ($response->failed()) {
                throw new \LemurHttpClient\Exception\ResponseException('Request failed', $response->getStatus());
            }
        }
        return $responses;
    }
    public function allSettled(array $requests): array
    {
        return $this->multiAdapter->sendAll($requests);
    }
    // Métodos para multipart y streaming pueden agregarse aquí según necesidades
}
