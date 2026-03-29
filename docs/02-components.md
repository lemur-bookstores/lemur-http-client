# Especificación de Componentes — LemurHttpClient

## Componentes principales

- **HttpClient**: Fachada principal, orquesta todos los componentes.
- **Request/Response**: Value objects inmutables para requests y respuestas.
- **RequestBuilder**: Builder fluido para requests.
- **CurlAdapter/CurlMultiAdapter**: Adaptadores para requests simples y concurrentes.
- **InterceptorPipeline**: Middleware para requests/responses.
- **RetryHandler**: Reintentos automáticos.
- **Auth**: Bearer, Basic, ApiKey, OAuth2.
- **Cache**: NullCache, ArrayCache, FileCache, Psr16CacheAdapter, CacheFactory.
- **Cookies**: Gestión de cookies.
- **MultipartBuilder**: Soporte para multipart/form-data.
- **StreamHandler**: Streaming de datos.
- **Excepciones**: Jerarquía robusta para todos los escenarios.

## Ejemplo de integración

```php
use LemurHttpClient\HttpClient;
use LemurHttpClient\Auth\BearerAuth;
use LemurHttpClient\Cache\ArrayCache;
use LemurHttpClient\RetryHandler;
use LemurHttpClient\InterceptorPipeline;

$client = new HttpClient([
    'auth' => new BearerAuth('TOKEN'),
    'cache' => new ArrayCache(),
    'retry' => new RetryHandler(3, 200),
    'pipeline' => new InterceptorPipeline()
]);
```

---

## 1. HttpClient

**Archivo:** `src/HttpClient.php`  
**Rol:** Fachada principal. Orquesta todos los demás componentes. Es el único objeto que el usuario instancia directamente.

### Contrato / Interface

```php
class HttpClient
{
    public function __construct(array $config = []);

    // Métodos HTTP
    public function get(string $url, array $options = []): Response;
    public function post(string $url, array $options = []): Response;
    public function put(string $url, array $options = []): Response;
    public function patch(string $url, array $options = []): Response;
    public function delete(string $url, array $options = []): Response;
    public function head(string $url, array $options = []): Response;
    public function options(string $url, array $options = []): Response;
    public function request(string $method, string $url, array $options = []): Response;

    // Concurrencia
    public function all(array $requests): array;           // Lanza excepción si alguno falla
    public function allSettled(array $requests): array;    // Retorna resultado de todos

    // Interceptors
    public function addRequestInterceptor(callable $fn): string;   // Retorna ID
    public function addResponseInterceptor(callable $fn): string;  // Retorna ID
    public function removeInterceptor(string $id): void;

    // Factory — crea instancia derivada con config adicional
    public function withOptions(array $options): self;
}
```

### Configuración Disponible (`$config`)

```php
[
    'base_url'         => '',          // URL base para todas las requests
    'timeout'          => 30,          // segundos
    'connect_timeout'  => 10,          // segundos
    'headers'          => [],          // headers por defecto
    'auth'             => null,        // AuthInterface por defecto
    'verify_ssl'       => true,        // verificar certificados SSL
    'allow_redirects'  => true,        // seguir redirects
    'max_redirects'    => 5,
    'cookies'          => false,       // persistir cookies entre requests
    'throw_on_error'   => false,       // lanzar excepción en 4xx/5xx
    'retry'            => null,        // RetryConfig array o null
    'cache'            => null,        // CacheInterface o null
    'user_agent'       => 'LemurHttpClient/1.0',
]
```

### Algoritmo de Resolución de URL

```
URL final = base_url + url
  Si url empieza con "http://" o "https://" → se usa directamente (ignora base_url)
  Si url empieza con "/" → base_url + url (sin duplicar slashes)
  Si url no empieza con "/" → base_url + "/" + url
```

### Estructura Interna

```php
private array $config;
private InterceptorPipeline $pipeline;
private CurlAdapter $adapter;
private CurlMultiAdapter $multiAdapter;
private ?CacheInterface $cache;
```

---

## 2. Request

**Archivo:** `src/Request/Request.php`  
**Rol:** Value Object inmutable que representa una HTTP request. Los interceptors reciben y retornan instancias de `Request`.

### Contrato

```php
final class Request
{
    public function __construct(
        string $method,
        string $url,
        array $headers = [],
        ?string $body = null,
        array $options = []
    );

    // Getters
    public function method(): string;
    public function url(): string;
    public function headers(): array;
    public function header(string $name): ?string;
    public function body(): ?string;
    public function option(string $key, $default = null);
    public function options(): array;

    // Mutators inmutables (retornan nueva instancia)
    public function withMethod(string $method): self;
    public function withUrl(string $url): self;
    public function withHeader(string $name, string $value): self;
    public function withHeaders(array $headers): self;
    public function withoutHeader(string $name): self;
    public function withBody(string $body): self;
    public function withOption(string $key, $value): self;
}
```

### Notas de Implementación

- Inmutabilidad via `clone` + modificación en los `withX()` methods
- Los headers se normalizan a `Header-Name: value` format internamente
- El body es siempre `string|null` — la serialización (JSON encode, form encode) ocurre en `RequestBuilder`

---

## 3. RequestBuilder

**Archivo:** `src/Request/RequestBuilder.php`  
**Rol:** Construye objetos `Request` a partir de las opciones del usuario. Maneja la serialización del body, construcción de query strings, y resolución de auth.

### Responsabilidades

```php
class RequestBuilder
{
    public function build(string $method, string $url, array $options, array $defaults): Request;
}
```

### Lógica de Construcción del Body

```
Si options['json'] está presente:
    body = json_encode(options['json'])
    Agregar header: Content-Type: application/json

Si options['form_params'] está presente:
    body = http_build_query(options['form_params'])
    Agregar header: Content-Type: application/x-www-form-urlencoded

Si options['multipart'] está presente:
    body = CurlMultipartBuilder::build(options['multipart'])
    Agregar header: Content-Type: multipart/form-data; boundary=...

Si options['body'] está presente (raw):
    body = options['body'] (string directo)
```

### Construcción de Query String

```
URL final = url base + "?" + http_build_query(options['query'])
Si la URL ya tiene "?", se concatena con "&"
```

### Algoritmo de Merging de Headers

```
1. Headers defaults del cliente
2. Headers de la opción 'headers' del request
3. Headers generados por auth (si existe)
4. Headers generados por el body (Content-Type)
Precedencia: más específico gana (4 > 3 > 2 > 1)
```

---

## 4. Response

**Archivo:** `src/Response/Response.php`  
**Rol:** Objeto completo que encapsula toda la información de una HTTP response. Inmutable.

### Contrato

```php
final class Response
{
    public function __construct(
        int $status,
        array $headers,
        string $body,
        array $info = [],   // curl_getinfo() output
        array $cookies = [],
        array $redirectHistory = []
    );

    // Estado HTTP
    public function status(): int;
    public function ok(): bool;              // 200-299
    public function successful(): bool;      // alias de ok()
    public function failed(): bool;          // >= 400
    public function clientError(): bool;     // 400-499
    public function serverError(): bool;     // >= 500
    public function redirected(): bool;      // hubo redirect

    // Body
    public function body(): string;
    public function json(bool $assoc = true): array;  // decode JSON
    public function xml(): \SimpleXMLElement;          // decode XML
    public function size(): int;                       // bytes

    // Headers
    public function headers(): array;
    public function header(string $name): ?string;
    public function contentType(): string;

    // Cookies
    public function cookies(): array;
    public function cookie(string $name): ?string;

    // Metadata
    public function redirectHistory(): array;
    public function timing(): array;         // connect_time, total_time, etc.

    // Encadenamiento tipo Promise
    public function then(callable $fn): mixed;
    public function otherwise(callable $fn): self;  // ejecuta si failed()
}
```

### Estructura Interna de `timing()`

```php
[
    'total_time'       => float,  // CURLINFO_TOTAL_TIME
    'connect_time'     => float,  // CURLINFO_CONNECT_TIME
    'namelookup_time'  => float,  // CURLINFO_NAMELOOKUP_TIME
    'pretransfer_time' => float,  // CURLINFO_PRETRANSFER_TIME
    'starttransfer_time' => float,// CURLINFO_STARTTRANSFER_TIME
    'redirect_time'    => float,  // CURLINFO_REDIRECT_TIME
]
```

### Parsing de Headers

Los headers de cURL llegan como string raw. El parser debe:
1. Separar por `\r\n`
2. Ignorar la primera línea (status line: `HTTP/1.1 200 OK`)
3. Split por `: ` para obtener nombre/valor
4. Normalizar nombres a lowercase para búsqueda case-insensitive

---

## 5. CurlAdapter

**Archivo:** `src/Adapter/CurlAdapter.php`  
**Rol:** Ejecutor de requests simples (una a la vez). Wrapper sobre la API de cURL de PHP.

### Contrato

```php
interface AdapterInterface
{
    public function send(Request $request): Response;
}

class CurlAdapter implements AdapterInterface
{
    public function send(Request $request): Response;
    private function buildHandle(Request $request): \CurlHandle;
    private function applyOptions(\CurlHandle $handle, Request $request): void;
    private function parseResponse(\CurlHandle $handle, string $raw): Response;
}
```

### Mapeo de Opciones → CURLOPT

```
url              → CURLOPT_URL
method GET       → CURLOPT_HTTPGET = true
method POST      → CURLOPT_POST = true + CURLOPT_POSTFIELDS
method PUT/PATCH → CURLOPT_CUSTOMREQUEST + CURLOPT_POSTFIELDS
method DELETE    → CURLOPT_CUSTOMREQUEST = 'DELETE'
headers          → CURLOPT_HTTPHEADER (array de "Name: Value")
timeout          → CURLOPT_TIMEOUT
connect_timeout  → CURLOPT_CONNECTTIMEOUT
verify_ssl=true  → CURLOPT_SSL_VERIFYPEER = true, CURLOPT_SSL_VERIFYHOST = 2
verify_ssl=false → CURLOPT_SSL_VERIFYPEER = false, CURLOPT_SSL_VERIFYHOST = 0
allow_redirects  → CURLOPT_FOLLOWLOCATION + CURLOPT_MAXREDIRS
cookies          → CURLOPT_COOKIEFILE = '' (memoria), CURLOPT_COOKIEJAR = ''
streaming        → CURLOPT_WRITEFUNCTION = callback
```

### CURLOPT Base (siempre aplicados)

```php
CURLOPT_RETURNTRANSFER => true,   // retornar body como string
CURLOPT_HEADER         => true,   // incluir headers en la respuesta
CURLOPT_ENCODING       => '',     // aceptar todos los encodings (gzip, deflate, etc.)
```

### Manejo de Errores cURL

```php
$result = curl_exec($handle);
if ($result === false) {
    $errno = curl_errno($handle);
    $error = curl_error($handle);
    
    match(true) {
        in_array($errno, [CURLE_COULDNT_CONNECT, CURLE_COULDNT_RESOLVE_HOST])
            => throw new ConnectionException($error, $errno),
        in_array($errno, [CURLE_OPERATION_TIMEOUTED])
            => throw new TimeoutException($error, $errno),
        default
            => throw new HttpException($error, $errno),
    };
}
```

---

## 6. CurlMultiAdapter

**Archivo:** `src/Adapter/CurlMultiAdapter.php`  
**Rol:** Ejecutor de múltiples requests en paralelo usando `curl_multi_*`. Soporta hasta 50 handles simultáneos.

### Contrato

```php
class CurlMultiAdapter
{
    public function sendAll(array $requests, bool $throwOnError = true): array;
    // Retorna array de Response (mismo orden que $requests)
    // Si throwOnError=false, los errores se retornan como Exception objects
}
```

### Algoritmo de Ejecución (curl_multi_exec)

```php
// 1. Inicializar multi handle
$multi = curl_multi_init();

// 2. Crear y agregar handles individuales
$handles = [];
foreach ($requests as $i => $request) {
    $handle = $this->adapter->buildHandle($request);
    curl_multi_add_handle($multi, $handle);
    $handles[$i] = $handle;
}

// 3. Ejecutar con select para evitar busy-waiting
do {
    $status = curl_multi_exec($multi, $active);
    if ($active) {
        // curl_multi_select bloquea hasta que hay actividad (máx 1s)
        // CPU usage: ~0% mientras espera I/O
        curl_multi_select($multi);
    }
} while ($active && $status === CURLM_OK);

// 4. Recolectar resultados
foreach ($handles as $i => $handle) {
    $raw = curl_multi_getcontent($handle);
    $responses[$i] = $this->parseResponse($handle, $raw);
    curl_multi_remove_handle($multi, $handle);
    curl_close($handle);
}

curl_multi_close($multi);
return $responses;
```

### Complejidad Temporal

- **O(n)** para setup y recolección
- **O(1) CPU** durante la espera (bloqueado en `curl_multi_select`)
- **Tiempo total** ≈ max(tiempo de cada request individual), no suma

---

## 7. InterceptorPipeline

**Archivo:** `src/Interceptor/InterceptorPipeline.php`  
**Rol:** Gestiona la cadena de interceptors de request y response. Patrón: Middleware Chain.

### Contrato

```php
interface InterceptorInterface
{
    // Implementar uno o ambos
    public function handleRequest(Request $request): Request;
    public function handleResponse(Response $response): Response;
}

class InterceptorPipeline
{
    public function addRequestInterceptor(callable $fn): string;   // retorna ID único
    public function addResponseInterceptor(callable $fn): string;
    public function removeInterceptor(string $id): void;
    public function processRequest(Request $request): Request;     // aplica cadena
    public function processResponse(Response $response): Response;
}
```

### Estructura Interna

```php
private array $requestInterceptors  = [];  // ['id' => callable]
private array $responseInterceptors = [];  // ['id' => callable]
```

### Algoritmo de Ejecución

```
Request interceptors → orden FIFO (registrado primero, ejecutado primero)
Response interceptors → orden LIFO (registrado primero, ejecutado último) — como onion model
```

### Generación de IDs

```php
private function generateId(): string
{
    return 'interceptor_' . uniqid('', true);
}
```

---

## 8. RetryHandler

**Archivo:** `src/Retry/RetryHandler.php`  
**Rol:** Envuelve la ejecución de una request con lógica de reintentos y backoff exponencial.

### Contrato

```php
class RetryHandler
{
    public function execute(callable $fn, array $config): Response;
}
```

### Configuración (`$config`)

```php
[
    'times'      => 3,              // número de reintentos máximos
    'delay'      => 100,            // delay base en ms
    'max_delay'  => 5000,           // delay máximo en ms (cap)
    'on_status'  => [429, 500, 502, 503, 504],  // HTTP status que disparan retry
    'on_exception' => true,         // reintentar en ConnectionException y TimeoutException
    'jitter'     => true,           // Full Jitter para evitar thundering herd
    'on_retry'   => null,           // callable opcional: fn(int $attempt, $exception) invocado antes de cada retry
]
```

### Algoritmo de Backoff (Full Jitter — AWS recommendation)

```php
private function calculateDelay(int $attempt, array $config): int
{
    $cap  = $config['max_delay'];
    $base = $config['delay'];

    // Exponential Backoff
    $exponential = min($cap, $base * (2 ** $attempt));

    // Full Jitter
    if ($config['jitter']) {
        return rand(0, $exponential);
    }

    return $exponential;
}

// Esperar en ms usando usleep
usleep($delayMs * 1000);
```

### Condiciones de Retry

```php
private function shouldRetry(int $attempt, array $config, $result): bool
{
    if ($attempt >= $config['times']) return false;

    if ($result instanceof \Exception) {
        return $config['on_exception'] &&
               ($result instanceof ConnectionException ||
                $result instanceof TimeoutException);
    }

    if ($result instanceof Response) {
        return in_array($result->status(), $config['on_status']);
    }

    return false;
}
```

---

## 9. Auth — Estrategias de Autenticación

**Directorio:** `src/Auth/`

### Interface Base

```php
interface AuthInterface
{
    /**
     * Aplica la autenticación al Request.
     * Retorna nuevo Request con los cambios (inmutabilidad).
     */
    public function apply(Request $request): Request;
}
```

### BearerAuth

```php
class BearerAuth implements AuthInterface
{
    public function __construct(string $token);
    public function apply(Request $request): Request
    {
        return $request->withHeader('Authorization', 'Bearer ' . $this->token);
    }
}
```

### BasicAuth

```php
class BasicAuth implements AuthInterface
{
    public function __construct(string $username, string $password);
    public function apply(Request $request): Request
    {
        $encoded = base64_encode($this->username . ':' . $this->password);
        return $request->withHeader('Authorization', 'Basic ' . $encoded);
    }
}
```

### ApiKeyAuth

```php
class ApiKeyAuth implements AuthInterface
{
    // $placement: 'header' (default) | 'query'
    public function __construct(string $name, string $value, string $placement = 'header');
    public function apply(Request $request): Request;
    // Si placement='header': agrega header $name: $value
    // Si placement='query': agrega query param al URL
}
```

### OAuth2Auth

```php
class OAuth2Auth implements AuthInterface
{
    public function __construct(array $config);
    // config: token_url, client_id, client_secret, scope, grant_type
    
    public function apply(Request $request): Request;
    // 1. Si no hay token o está expirado → solicitar nuevo token via client_credentials
    // 2. Aplicar como Bearer token
    // 3. El token se cachea en memoria durante su TTL (expires_in)
    
    private function fetchToken(): void;  // POST al token_url
    private function isTokenExpired(): bool;
}
```

**Nota:** `OAuth2Auth` requiere que la extensión cURL esté disponible (lo cual es requisito base del framework) para solicitar el token. No requiere dependencias externas.

---

## 10. Cache

**Directorio:** `src/Cache/`

### Interface

```php
interface CacheInterface
{
    public function get(string $key): ?Response;
    public function set(string $key, Response $response, int $ttl = 300): void;
    public function has(string $key): bool;
    public function delete(string $key): void;
    public function clear(): void;
}
```

### NullCache (Default — disabled)

```php
class NullCache implements CacheInterface
{
    // Todos los métodos son no-op
    public function get(string $key): ?Response { return null; }
    public function set(string $key, Response $response, int $ttl = 300): void {}
    public function has(string $key): bool { return false; }
    // ...
}
```

### ArrayCache (In-Memory)

```php
class ArrayCache implements CacheInterface
{
    private array $store = [];  // ['key' => ['response' => Response, 'expires_at' => int]]

    public function get(string $key): ?Response
    {
        if (!isset($this->store[$key])) return null;
        if (time() > $this->store[$key]['expires_at']) {
            unset($this->store[$key]);
            return null;
        }
        return $this->store[$key]['response'];
    }
}
```

### Generación de Cache Key

```php
private function buildCacheKey(Request $request): string
{
    return md5($request->method() . '|' . $request->url() . '|' . json_encode($request->headers()));
}
```

**Solo se cachean requests GET y HEAD.** POST/PUT/PATCH/DELETE nunca se cachean.

---

## 11. CancelToken

**Archivo:** `src/Support/CancelToken.php`  
**Rol:** Mecanismo para cancelar requests en curso.

### Contrato

```php
class CancelToken
{
    private bool $cancelled = false;
    private ?string $reason = null;

    public function cancel(string $reason = 'Cancelled'): void;
    public function isCancelled(): bool;
    public function reason(): ?string;
    public function throwIfCancelled(): void; // lanza CancelledException si está cancelado
}
```

### Mecanismo de Cancelación con curl_multi

```php
// El CurlMultiAdapter verifica el token en cada iteración del loop
do {
    $status = curl_multi_exec($multi, $active);

    if ($cancelToken !== null && $cancelToken->isCancelled()) {
        // Remover y cerrar todos los handles pendientes
        foreach ($handles as $handle) {
            curl_multi_remove_handle($multi, $handle);
            curl_close($handle);
        }
        curl_multi_close($multi);
        throw new CancelledException($cancelToken->reason());
    }

    if ($active) {
        curl_multi_select($multi);
    }
} while ($active && $status === CURLM_OK);
```

---

## 12. Exception Hierarchy

**Directorio:** `src/Exception/`

```php
// Base
class HttpException extends \RuntimeException {}

// No se pudo establecer conexión
class ConnectionException extends HttpException {}

// La request excedió el tiempo de espera
class TimeoutException extends HttpException {}

// La request está mal formada (URL inválida, headers malformados, etc.)
class RequestException extends HttpException {}

// Response HTTP con código de error (solo cuando throw_on_error=true)
class ResponseException extends HttpException {
    public function __construct(Response $response);
    public function response(): Response;
    public function status(): int;
}

// Request cancelada por CancelToken
class CancelledException extends HttpException {
    public function __construct(string $reason = 'Cancelled');
}
```

---

## Resumen de Complejidades Algorítmicas

| Componente | Operación | Complejidad Temporal | Complejidad Espacial |
|------------|-----------|---------------------|---------------------|
| RequestBuilder | build() | O(n) headers | O(n) headers |
| InterceptorPipeline | processRequest/Response | O(k) interceptors | O(1) |
| RetryHandler | execute() | O(attempts) | O(1) |
| CurlAdapter | send() | O(1) | O(body size) |
| CurlMultiAdapter | sendAll(n) | O(n) setup + O(max time) | O(n) handles |
| ArrayCache | get/set | O(1) | O(entries) |
| Response::json() | decode | O(n) body size | O(parsed JSON) |

---

*Próximo documento: Plan de Tareas →*
