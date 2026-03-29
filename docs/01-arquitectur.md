# Documento de Arquitectura — LemurHttpClient Framework

**Versión:** 1.0.0  
**Estado:** Listo para producción

---

## 1. Visión del Producto

Framework HTTP para PHP inspirado en Axios, sin dependencias externas, con soporte para:
- Requests concurrentes (curl_multi)
- Interceptors (middleware)
- Autenticación (Bearer, Basic, API Key, OAuth2)
- Caché (array, archivo, PSR-16)
- Cookies
- Multipart/form-data
- Streaming
- Manejo robusto de errores

## 2. Diagrama de Componentes

```
+-------------------+
|   HttpClient      |
+-------------------+
| - InterceptorPipeline
| - RetryHandler
| - Auth
| - Cache
| - Cookies
| - MultipartBuilder
| - StreamHandler
| - CurlAdapter
| - CurlMultiAdapter
+-------------------+
```

## 3. Flujo de una petición

1. El usuario crea un `HttpClient` y configura los componentes.
2. Se construye un `Request` (con builder o directamente).
3. Se aplican interceptors y autenticación.
4. Se consulta la caché (si aplica).
5. Se ejecuta la petición (con retry si corresponde).
6. Se procesan cookies, multipart y streaming si es necesario.
7. Se aplican interceptors de respuesta.
8. Se retorna un `Response` completo.

## 4. Componentes principales

- Ver [docs/02-components.md](docs/02-components.md)

---

## 5. Estructura de Directorios

```
lemur-http-client/
├── src/
│   ├── HttpClient.php              # Punto de entrada principal
│   ├── Request/
│   │   ├── Request.php             # Value Object inmutable
│   │   └── RequestBuilder.php      # Builder fluido
│   ├── Response/
│   │   └── Response.php            # Objeto de respuesta completo
│   ├── Adapter/
│   │   ├── AdapterInterface.php    # Contrato de adaptador
│   │   ├── CurlAdapter.php         # Request simple (curl_exec)
│   │   └── CurlMultiAdapter.php    # Requests concurrentes (curl_multi_*)
│   ├── Auth/
│   │   ├── AuthInterface.php       # Contrato de autenticación
│   │   ├── BearerAuth.php
│   │   ├── BasicAuth.php
│   │   ├── ApiKeyAuth.php
│   │   └── OAuth2Auth.php
│   ├── Interceptor/
│   │   ├── InterceptorInterface.php
│   │   └── InterceptorPipeline.php
│   ├── Retry/
│   │   └── RetryHandler.php        # Backoff exponencial + jitter
│   ├── Cache/
│   │   ├── CacheInterface.php
│   │   ├── NullCache.php           # Default (no-op)
│   │   └── ArrayCache.php          # Cache en memoria
│   ├── Support/
│   │   ├── CancelToken.php         # Cancelación de requests
│   │   └── MultiResponse.php       # Resultado de ::all()
│   └── Exception/
│       ├── HttpException.php       # Base exception
│       ├── ConnectionException.php
│       ├── TimeoutException.php
│       └── RequestException.php
├── tests/
├── composer.json
└── README.md
```

---

## 6. API Pública del Framework

### 6.1 Uso Básico

```php
use LemurHttpClient\HttpClient;

// Instancia con configuración global
$client = new HttpClient([
    'base_url'  => 'https://api.example.com',
    'timeout'   => 30,
    'headers'   => ['Accept' => 'application/json'],
]);

// Request simple — almacenable en variable
$response = $client->get('/users');
$users    = $response->json();

// Encadenamiento fluido
$client->get('/users')->then(function($res) {
    var_dump($res->json());
});
```

### 6.2 Métodos HTTP

```php
$client->get('/resource', $options);
$client->post('/resource', $options);
$client->put('/resource', $options);
$client->patch('/resource', $options);
$client->delete('/resource', $options);
$client->head('/resource', $options);
$client->options('/resource', $options);
$client->request('CUSTOM', '/resource', $options);
```

### 6.3 Opciones de Request

```php
$client->post('/users', [
    'json'        => ['name' => 'John', 'email' => 'j@j.com'],  // body JSON auto-encode
    'form_params' => ['field' => 'value'],                       // application/x-www-form-urlencoded
    'multipart'   => [['name' => 'file', 'contents' => $stream, 'filename' => 'file.pdf']],
    'headers'     => ['X-Custom-Header' => 'value'],
    'query'       => ['page' => 1, 'limit' => 20],
    'timeout'     => 30,
    'auth'        => new BearerAuth('my-token'),
    'retry'       => ['times' => 3, 'delay' => 100, 'on_status' => [429, 503]],
    'cancel'      => $cancelToken,
    'allow_redirects' => true,
    'verify_ssl'  => true,
    'cookies'     => true,
    'stream'      => false,
    'on_headers'  => fn($headers) => null,
    'cache'       => true,
    'cache_ttl'   => 300,
]);
```

### 6.4 Requests Concurrentes

```php
// Equivalente a axios.all() / Promise.all()
[$users, $posts, $tags] = $client->all([
    $client->get('/users'),
    $client->get('/posts'),
    $client->get('/tags'),
]);

// Con manejo individual de errores
$results = $client->allSettled([
    $client->get('/users'),
    $client->get('/may-fail'),
]);

foreach ($results as $result) {
    if ($result->isSuccess()) {
        echo $result->response()->json();
    } else {
        echo $result->exception()->getMessage();
    }
}
```

### 6.5 Objeto Response

```php
$res = $client->get('/users');

$res->json();            // array — body decodificado como JSON
$res->body();            // string — body crudo
$res->status();          // int — código HTTP (200, 404, etc.)
$res->headers();         // array — todos los headers de respuesta
$res->header('Content-Type'); // string — header específico
$res->cookies();         // array — cookies recibidas
$res->ok();              // bool — status entre 200-299
$res->successful();      // bool — alias de ok()
$res->failed();          // bool — status >= 400
$res->serverError();     // bool — status >= 500
$res->clientError();     // bool — status >= 400 < 500
$res->redirected();      // bool — hubo al menos un redirect
$res->redirectHistory(); // array — historial de redirects
$res->timing();          // array — CURLINFO_TOTAL_TIME, CURLINFO_CONNECT_TIME, etc.
$res->size();            // int — tamaño de la respuesta en bytes
$res->then(callable);    // Encadenamiento tipo Promise
```

### 6.6 Autenticación

```php
use LemurHttpClient\Auth\BearerAuth;
use LemurHttpClient\Auth\BasicAuth;
use LemurHttpClient\Auth\ApiKeyAuth;
use LemurHttpClient\Auth\OAuth2Auth;

// Bearer Token
$client->get('/protected', ['auth' => new BearerAuth('my-token')]);

// Basic Auth
$client->get('/protected', ['auth' => new BasicAuth('user', 'pass')]);

// API Key en header
$client->get('/protected', ['auth' => new ApiKeyAuth('X-API-Key', 'key-value')]);

// API Key en query param
$client->get('/protected', ['auth' => new ApiKeyAuth('api_key', 'key-value', 'query')]);

// OAuth2 con refresh automático
$oauth = new OAuth2Auth([
    'token_url'     => 'https://auth.server.com/token',
    'client_id'     => 'app-id',
    'client_secret' => 'app-secret',
    'scope'         => 'read write',
]);
$client->get('/protected', ['auth' => $oauth]);
```

### 6.7 Interceptors

```php
// Request interceptor — ejecuta ANTES de enviar
$client->addRequestInterceptor(function(Request $req): Request {
    return $req->withHeader('X-Request-Id', uniqid('req_'));
});

// Response interceptor — ejecuta AL RECIBIR respuesta
$client->addResponseInterceptor(function(Response $res): Response {
    if ($res->status() === 401) {
        throw new UnauthorizedException();
    }
    return $res;
});

// Eliminar interceptors
$id = $client->addRequestInterceptor($fn);
$client->removeInterceptor($id);
```

### 6.8 Cancelación

```php
use LemurHttpClient\Support\CancelToken;

$cancel = new CancelToken();

// En otro hilo / proceso (o después de X tiempo)
// $cancel->cancel('Timeout manual del usuario');

$response = $client->get('/slow-endpoint', [
    'cancel' => $cancel,
]);
```

---

## 7. Análisis de Alternativas — Decisiones Técnicas

### 7.1 Modelo de Concurrencia

| Alternativa | Pros | Contras | Requiere |
|-------------|------|---------|----------|
| `curl_multi_exec` | Nativo PHP 5+, cero deps, battle-tested, polling eficiente | API verbose de bajo nivel | ext-curl ✅ |
| PHP Fibers (8.1+) | Async real, API limpia | PHP 8.1 mínimo — excluye PHP 7.4 | PHP 8.1 ❌ |
| ReactPHP | Event loop real, streams | Dependencia externa — rompe "zero deps" | Composer ❌ |
| Swoole/OpenSwoole | Coroutines reales | Extensión especial, no universal | ext-swoole ❌ |

**Decisión: `curl_multi_exec`** — única opción que cumple PHP 7.4 + zero dependencies.

### 7.2 Patrón de Interceptors

| Alternativa | Pros | Contras |
|-------------|------|---------|
| Array de callables (elegido) | Simple, PHP 7.4 compatible, bajo overhead | Sin tipado fuerte en callable |
| PSR-15 Middleware | Estándar, tipado | Requiere PSR packages — rompe zero deps |
| Decorator Chain | OOP puro | Verboso para registrar |

**Decisión: Array de callables** con `InterceptorInterface` opcional para tipado.

### 7.3 Inmutabilidad del Request

El objeto `Request` es **inmutable** (value object). Cada modificación retorna una nueva instancia via `withX()` methods. Esto garantiza que los interceptors no produzcan side effects inesperados.

### 7.4 Estrategia de Retry (Backoff)

Se implementa **Exponential Backoff con Full Jitter** (recomendado por AWS):

```
delay = random(0, min(cap, base * 2^attempt))
```

- `base`: delay inicial (ms) configurable
- `cap`: máximo delay (ms) configurable  
- `jitter`: Full Jitter por defecto (evita thundering herd)
- Reintentos solo en: errores de red, timeouts, y status codes configurables (ej. 429, 503)

---

## 8. Análisis de Consumo de Recursos

### 8.1 Memoria por Request

| Componente | Uso estimado |
|------------|-------------|
| Objeto `Request` | ~1-2 KB |
| Handle cURL | ~50 KB (en C, manejado por ext-curl) |
| Buffer de respuesta | Tamaño del body (streaming evita carga completa en memoria) |
| Interceptor pipeline | ~1 KB por interceptor registrado |
| **Total baseline** | ~60-70 KB por request activa |

### 8.2 Concurrencia: 50 requests simultáneos

| Recurso | Estimado |
|---------|----------|
| Handles cURL activos | 50 × ~50 KB = ~2.5 MB (en ext-curl) |
| Objetos PHP en memoria | 50 × ~5 KB = ~250 KB |
| **Total estimado** | ~3 MB peak durante la ráfaga |

> ✅ Completamente asumible. PHP con `memory_limit = 128M` tiene amplio margen.

### 8.3 CPU durante `curl_multi_exec`

`curl_multi_exec` usa **select/poll** del SO para esperar I/O. El uso de CPU durante la espera es mínimo (blocking en I/O, no en CPU). El patrón recomendado incluye `curl_multi_select()` para evitar busy-waiting.

---

## 9. Manejo de Errores

### 9.1 Jerarquía de Excepciones

```
\Exception
  └── LemurHttpClient\Exception\HttpException          # Base
        ├── ConnectionException               # No se pudo conectar
        ├── TimeoutException                  # CURLE_OPERATION_TIMEOUTED
        ├── RequestException                  # Error en la request (malformed)
        └── ResponseException                 # HTTP >= 400 (opcional, configurable)
```

### 9.2 Comportamiento por Defecto

- Por defecto, el framework **NO** lanza excepción en HTTP 4xx/5xx (igual que Axios).
- El usuario verifica con `$res->failed()` o `$res->status()`.
- Se puede activar `throw_on_error => true` para lanzar `ResponseException` automáticamente en 4xx/5xx.

---

## 10. Compatibilidad

| Entorno | Soporte |
|---------|---------|
| PHP 7.4 | ✅ Completo |
| PHP 8.0 | ✅ Completo |
| PHP 8.1+ | ✅ Completo (sin Fibers — no necesarios) |
| Apache + mod_php | ✅ |
| PHP-FPM | ✅ |
| CLI (scripts) | ✅ |
| Windows (MAMP/XAMPP) | ✅ |
| Docker | ✅ |
| Laravel / Symfony | ✅ Como librería standalone |
| Moodle Plugins | ✅ PHP 7.4+ compatible |

---

*Próximo documento: Especificación de Componentes →*
