# Plan de Tareas — LemurHttpClient Framework
**Versión:** 1.0.0  
**Documento:** 3 de 3  
**Dependencia:** Arquitectura v1.0.0 + Componentes v1.0.0

---

## Resumen Ejecutivo

| Fase | Componentes | Tareas | Estimación |
|------|-------------|--------|------------|
| 1 — Core | Request, Response, CurlAdapter | 8 tareas | 3–4 h |
| 2 — Concurrencia | CurlMultiAdapter, CancelToken | 5 tareas | 2–3 h |
| 3 — Middleware | InterceptorPipeline, RetryHandler | 5 tareas | 2 h |
| 4 — Auth | BearerAuth, BasicAuth, ApiKeyAuth, OAuth2 | 6 tareas | 2–3 h |
| 5 — Extras | Cache, Cookies, Streaming, Multipart | 6 tareas | 2–3 h |
| 6 — HttpClient | Fachada principal, integración total | 5 tareas | 2 h |
| 7 — Packaging | composer.json, README, ejemplos | 4 tareas | 1 h |
| **Total** | | **39 tareas** | **14–18 h** |

---

## Fase 1 — Core Fundamentals

> **Objetivo:** Request + Response + Adaptador simple funcional. Al final de esta fase, una request GET básica debe funcionar.

### T-01: Estructura de directorios y composer.json
- Crear árbol de directorios `src/` según especificación
- Crear `composer.json` con autoload PSR-4 (`LemurHttpClient\\` → `src/`)
- Requerir solo `php: >=7.4` y `ext-curl: *`
- **Criterio de aceptación:** `composer install` y autoload funciona

### T-02: Implementar `Request` (Value Object inmutable)
- Constructor con `method`, `url`, `headers`, `body`, `options`
- Métodos `withX()` que clonan y modifican
- Normalización de header names (`content-type` → `Content-Type`)
- **Criterio de aceptación:** Inmutabilidad verificada — `withHeader()` no modifica original

### T-03: Implementar `RequestBuilder`
- Método `build(string $method, string $url, array $options, array $defaults): Request`
- Serialización de `json`, `form_params`, `multipart` (defer multipart a Fase 5)
- Construcción de query string via `http_build_query()`
- Merging de headers con precedencia correcta
- Resolución de URL (base_url + url)
- **Criterio de aceptación:** Todas las combinaciones de body type producen headers correctos

### T-04: Implementar `Response` (objeto completo)
- Constructor con `status`, `headers`, `body`, `info`, `cookies`, `redirectHistory`
- Todos los métodos de estado: `ok()`, `failed()`, `clientError()`, `serverError()`
- `json()`, `body()`, `xml()`, `size()`
- `headers()`, `header(string $name)` (case-insensitive)
- `cookies()`, `cookie(string $name)`
- `redirectHistory()`, `timing()`
- `then(callable)`, `otherwise(callable)`
- **Criterio de aceptación:** `$res->header('content-type')` == `$res->header('Content-Type')`

### T-05: Implementar parser de response HTTP raw
- Separar headers del body (split por `\r\n\r\n`)
- Parser de headers individuales
- Parser de cookies desde `Set-Cookie` headers
- Parser de redirect history desde `CURLINFO_REDIRECT_URL`
- **Criterio de aceptación:** Response con múltiples cookies parseada correctamente

### T-06: Implementar `CurlAdapter`
- `buildHandle(Request): CurlHandle` — mapea opciones a CURLOPT_*
- `applyOptions(CurlHandle, Request): void` — todos los CURLOPT documentados
- `send(Request): Response` — ejecuta y parsea
- Manejo de errores cURL → excepciones tipadas
- `curl_getinfo()` → array timing completo
- **Criterio de aceptación:** GET a `https://httpbin.org/get` retorna Response con status 200

### T-07: Implementar jerarquía de excepciones
- `HttpException` (base)
- `ConnectionException`
- `TimeoutException`
- `RequestException`
- `ResponseException` (con `response()` method)
- `CancelledException`
- **Criterio de aceptación:** Cada tipo se puede capturar independientemente

### T-08: Test de integración Fase 1
- Smoke test: GET, POST con JSON body
- Verificar que headers de respuesta se parsean
- Verificar timing en `$res->timing()`
- **Criterio de aceptación:** Tests pasan contra `https://httpbin.org`

---

## Fase 2 — Concurrencia y Cancelación

> **Objetivo:** `$client->all([...])` funcional con hasta 50 requests paralelas.

### T-09: Implementar `CurlMultiAdapter`
- `sendAll(array $requests, bool $throwOnError): array`
- Loop `curl_multi_exec` con `curl_multi_select` (sin busy-wait)
- Recolección de resultados en orden original
- Manejo de errores por handle individual
- **Criterio de aceptación:** 10 requests en paralelo terminan en tiempo ≈ max(individual), no suma

### T-10: Implementar `CancelToken`
- `cancel(string $reason)`, `isCancelled()`, `reason()`, `throwIfCancelled()`
- **Criterio de aceptación:** Token creado, cancelado, y verificado correctamente

### T-11: Integrar `CancelToken` en `CurlMultiAdapter`
- Verificar token en cada iteración del loop `curl_multi_exec`
- Al cancelar: remover todos los handles, cerrar multi handle, lanzar `CancelledException`
- **Criterio de aceptación:** Request cancelada no genera Response ni queda handle abierto

### T-12: Implementar `MultiResponse` (resultado de allSettled)
- Wrapper que contiene `Response|Exception` para cada request
- Métodos `isSuccess()`, `response()`, `exception()`
- **Criterio de aceptación:** Mix de éxitos y fallos en `allSettled()` es manejado sin crash

### T-13: Test de concurrencia
- 20 requests paralelas a httpbin.org
- `allSettled()` con una URL inválida en el mix
- Cancelación a mitad del batch
- **Criterio de aceptación:** Todos los tests pasan, sin resource leaks (handles cURL cerrados)

---

## Fase 3 — Middleware e Interceptors

> **Objetivo:** Sistema de interceptors funcional + reintentos automáticos.

### T-14: Implementar `InterceptorPipeline`
- `addRequestInterceptor(callable): string` (retorna ID único)
- `addResponseInterceptor(callable): string`
- `removeInterceptor(string $id): void`
- `processRequest(Request): Request` — FIFO
- `processResponse(Response): Response` — LIFO
- **Criterio de aceptación:** Orden de ejecución verificado con interceptors que agregan headers trazables

### T-15: Implementar `InterceptorInterface` (opcional para typing)
- Interface con `handleRequest` y `handleResponse`
- El pipeline acepta tanto callables como objetos que implementan la interface
- **Criterio de aceptación:** Ambas formas de registro funcionan

### T-16: Implementar `RetryHandler`
- `execute(callable $fn, array $config): Response`
- Condiciones de retry: excepciones de red + status codes configurables
- Backoff exponencial con Full Jitter (`rand(0, min(cap, base * 2^attempt))`)
- `usleep()` entre intentos
- Callback `on_retry` opcional
- **Criterio de aceptación:** 3 intentos con delay creciente observable en logs

### T-17: Test de Interceptors y Retry
- Interceptor que agrega header de tracing
- Interceptor que lanza excepción en 401
- Retry en status 503 con 3 intentos
- **Criterio de aceptación:** Comportamiento verificado unitariamente con mock de adapter

### T-18: Integrar pipeline en flujo principal (preparatorio para Fase 6)
- El `HttpClient` instanciará el pipeline
- El adapter será invocado dentro del pipeline
- **Criterio de aceptación:** Diagrama de flujo de arquitectura se mapea al código

---

## Fase 4 — Autenticación

> **Objetivo:** Los 4 métodos de auth funcionando, inyectables en cualquier request.

### T-19: Implementar `AuthInterface`
- `apply(Request $request): Request`
- **Criterio de aceptación:** Interface definida, lista para implementaciones

### T-20: Implementar `BearerAuth` y `BasicAuth`
- `BearerAuth(string $token)` → `Authorization: Bearer {token}`
- `BasicAuth(string $user, string $pass)` → `Authorization: Basic {base64}`
- **Criterio de aceptación:** Headers generados verificados con `httpbin.org/headers`

### T-21: Implementar `ApiKeyAuth`
- `ApiKeyAuth(string $name, string $value, string $placement = 'header')`
- Placement `'header'` → agrega header
- Placement `'query'` → modifica URL agregando query param
- **Criterio de aceptación:** Ambos placement producen el resultado correcto

### T-22: Implementar `OAuth2Auth`
- Constructor con `token_url`, `client_id`, `client_secret`, `scope`, `grant_type` (default: `client_credentials`)
- Solicitud de token via POST (usando cURL interno)
- Cache del token en memoria durante `expires_in` segundos
- Refresh automático cuando el token expira
- **Criterio de aceptación:** Token solicitado solo una vez para múltiples requests sucesivas

### T-23: Integrar Auth en `RequestBuilder`
- Si `options['auth']` es un `AuthInterface`, llamar `$auth->apply($request)`
- Si `options['auth']` es array `['user', 'pass']` → usar `BasicAuth` automáticamente
- **Criterio de aceptación:** Auth aplicado correctamente antes de los interceptors

### T-24: Test de autenticación
- Bearer con token mock
- Basic con httpbin.org/basic-auth
- ApiKey en header y en query
- **Criterio de aceptación:** httpbin.org confirma los headers recibidos

---

## Fase 5 — Features Adicionales

> **Objetivo:** Multipart, cookies, streaming y caché opcionales.

### T-25: Implementar upload de archivos (multipart/form-data)
- En `RequestBuilder`: manejar `options['multipart']`
- Usar `CURLFile` (PHP 5.5+) para archivos en multipart
- Generar boundary único por request
- Soporte para: file path string, `CURLFile`, y content string (para campos de texto en multipart)
- **Criterio de aceptación:** Upload de archivo a `httpbin.org/post` recibido correctamente

### T-26: Implementar manejo de Cookies
- Opción `cookies: true` → habilitar cookie jar en memoria (`CURLOPT_COOKIEFILE = ''`)
- Opción `cookies: string` → path a archivo para persistencia en disco
- `$response->cookies()` retorna array parseado de `Set-Cookie`
- **Criterio de aceptación:** Cookie recibida en response 1 es enviada automáticamente en request 2 (misma instancia de client)

### T-27: Implementar Streaming
- Opción `stream: true` → `CURLOPT_WRITEFUNCTION` con callback
- Opción `on_data: callable` → llamado con cada chunk recibido
- Útil para descarga de archivos grandes sin cargar body en memoria
- **Criterio de aceptación:** Descarga de archivo de 10MB con uso de memoria constante (< 5MB)

### T-28: Implementar `NullCache` y `ArrayCache`
- `NullCache` — todos los métodos son no-op
- `ArrayCache` — store en array PHP con TTL y expiration via `time()`
- Generación de cache key: `md5(method|url|json(headers))`
- Solo cachear GET/HEAD
- **Criterio de aceptación:** Segunda request idéntica retorna Response sin usar cURL

### T-29: Implementar `CacheInterface` + integración en flujo
- Antes del adapter: consultar cache
- Después del adapter: guardar en cache si corresponde
- `cache_ttl` en opciones de request sobrescribe default del cliente
- **Criterio de aceptación:** Cache hit verificado inspeccionando que curl no fue invocado

### T-30: Test de features adicionales
- Upload de imagen a httpbin.org
- Cookie persistida entre requests
- Streaming de archivo con callback
- Cache hit y miss
- **Criterio de aceptación:** Todos los tests pasan

---

## Fase 6 — HttpClient (Integración Total)

> **Objetivo:** La fachada principal funciona como punto único de entrada, integrando todos los componentes.

### T-31: Implementar `HttpClient` — constructor y config
- Constructor con merge de `$config` sobre defaults
- Instanciación interna de: `CurlAdapter`, `CurlMultiAdapter`, `InterceptorPipeline`
- Config store: `$this->config` como array privado
- **Criterio de aceptación:** Instanciación sin errores

### T-32: Implementar métodos HTTP en `HttpClient`
- `get()`, `post()`, `put()`, `patch()`, `delete()`, `head()`, `options()`, `request()`
- Todos delegan a: `RequestBuilder::build()` → pipeline → `RetryHandler` → `CurlAdapter`
- Opción `throw_on_error` verificada al recibir Response
- **Criterio de aceptación:** Los 7 métodos HTTP funcionan con httpbin.org

### T-33: Implementar `all()` y `allSettled()` en `HttpClient`
- `all()` → `CurlMultiAdapter::sendAll(throwOnError: true)`
- `allSettled()` → `CurlMultiAdapter::sendAll(throwOnError: false)`
- Pasar `CancelToken` al multi adapter si existe en opciones
- **Criterio de aceptación:** Batch de 10 requests exitoso + allSettled con errores manejado

### T-34: Implementar `withOptions()` (factory method)
- Retorna `clone` del cliente con opciones adicionales mergeadas
- Útil para crear clientes derivados (e.g., cliente autenticado a partir de base)
- **Criterio de aceptación:** `$authClient = $client->withOptions(['auth' => $bearer])` funciona independientemente del original

### T-35: Test de integración total
- E2E completo: instancia → interceptor → auth → request → retry → cache → response
- Test de `withOptions()`
- Test de error global con interceptor de 401
- **Criterio de aceptación:** Flujo completo verificado sin errores

---

## Fase 7 — Packaging y Documentación

### T-36: `composer.json` final
- Nombre: `LemurHttpClient/LemurHttpClient` (o nombre elegido)
- Descripción, keywords, license (MIT)
- Autoload PSR-4
- `require`: `php: >=7.4`, `ext-curl: *`
- `require-dev`: herramienta de test (PHPUnit 9.x — compatible con PHP 7.4)
- **Criterio de aceptación:** `composer validate` pasa

### T-37: README.md con Quick Start
- Instalación via Composer
- Ejemplos de los casos de uso más comunes (GET, POST, auth, all())
- Tabla de opciones disponibles
- **Criterio de aceptación:** Un dev puede usar el framework sin leer más documentación

### T-38: Archivo de ejemplos (`examples/`)
- `basic.php` — GET y POST simple
- `concurrent.php` — `all()` con 5 requests
- `auth.php` — los 4 métodos de auth
- `interceptors.php` — request ID + global error handler
- `retry.php` — retry con 503 simulado
- **Criterio de aceptación:** Todos los ejemplos ejecutan sin errores

### T-39: Revisión final y tag v1.0.0
- Revisar que no hay `var_dump()` o `echo` de debug en el código
- Revisar docblocks en métodos públicos
- Verificar que todos los archivos tienen namespace correcto
- Git tag `v1.0.0`
- **Criterio de aceptación:** Revisión completa por al menos un miembro del equipo

---

## Diagrama de Dependencias entre Tareas

```
T-01 (setup)
  └──► T-02 (Request)
         └──► T-03 (RequestBuilder)
         └──► T-04 (Response)
               └──► T-05 (Response Parser)
                      └──► T-06 (CurlAdapter)
                             └──► T-07 (Exceptions) [paralelo]
                             └──► T-08 (Test Fase 1)
                                    └──► T-09 (MultiAdapter)
                                           └──► T-10 (CancelToken)
                                           └──► T-11 (Cancel integration)
                                           └──► T-12 (MultiResponse)
                                           └──► T-13 (Test Fase 2)
                                                  └──► T-14 (InterceptorPipeline)
                                                  └──► T-16 (RetryHandler)
                                                  └──► T-17 (Test Fase 3)
                                                         └──► T-19 (AuthInterface)
                                                               ├──► T-20 (Bearer+Basic)
                                                               ├──► T-21 (ApiKey)
                                                               ├──► T-22 (OAuth2)
                                                               └──► T-23 (Auth en Builder)
                                                                      └──► T-24 (Test Fase 4)
                                                                             └──► T-25..T-30 (Fase 5)
                                                                                    └──► T-31..T-35 (HttpClient)
                                                                                           └──► T-36..T-39 (Packaging)
```

---

## Criterios de Calidad Global

| Criterio | Target |
|----------|--------|
| Zero dependencias en producción | ✅ Solo ext-curl |
| Compatibilidad PHP mínima | ✅ PHP 7.4 |
| Cobertura de tests | ≥ 80% líneas en componentes core |
| Memory leak | Ningún handle cURL sin cerrar tras cada request |
| Tiempo de respuesta overhead | < 1ms de overhead del framework sobre cURL puro |
| Tamaño del package | < 50 KB (sin tests y docs) |

---

## Comando de Inicio Rápido

```bash
mkdir lemur-http-client && cd lemur-http-client
composer init --name="LemurHttpClient/LemurHttpClient" --require="php:>=7.4" --require="ext-curl:*"
mkdir -p src/{Request,Response,Adapter,Auth,Interceptor,Retry,Cache,Support,Exception}
# → Proceder con T-01
```

---

*Equipo: Software Architecture · Software Engineer · Data Structure Engineer · Steve Jobs (Product Leader)*  
*Documentos listos. Esperando confirmación del usuario para iniciar Fase 5 (Generación de Código).*
