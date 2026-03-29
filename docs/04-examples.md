# Ejemplos de Uso — LemurHttpClient

A continuación se muestran ejemplos prácticos para utilizar el framework LemurHttpClient en PHP.

---

## 1. Realizar una petición GET

```php
require 'vendor/autoload.php';

use LemurHttpClient\HttpClient;

$client = new HttpClient();
$response = $client->get('https://jsonplaceholder.typicode.com/posts/1');

if ($response->ok()) {
    echo $response->getBody();
} else {
    echo 'Error: ' . $response->getStatus();
}
```

---

## 2. Realizar una petición POST con JSON

```php
use LemurHttpClient\HttpClient;

$client = new HttpClient();
$response = $client->post('https://jsonplaceholder.typicode.com/posts', [
    'json' => [
        'title' => 'foo',
        'body' => 'bar',
        'userId' => 1
    ]
]);

echo $response->getStatus();
echo $response->getBody();
```

---

## 3. Personalizar headers y opciones

```php
use LemurHttpClient\HttpClient;

$client = new HttpClient([
    'headers' => [
        'Authorization' => 'Bearer TU_TOKEN',
        'Accept' => 'application/json'
    ]
]);
$response = $client->get('https://api.ejemplo.com/data');
```

---

## 4. Manejo de errores

```php
use LemurHttpClient\HttpClient;

$client = new HttpClient();
$response = $client->get('https://api.ejemplo.com/404');

if ($response->clientError()) {
    echo 'Error del cliente: ' . $response->getStatus();
}
```

---

## 5. Obtener respuesta como JSON

```php
use LemurHttpClient\HttpClient;

$client = new HttpClient();
$response = $client->get('https://jsonplaceholder.typicode.com/posts/1');
$data = $response->json();
print_r($data);
```

---

## 6. Autenticación Bearer

```php
use LemurHttpClient\HttpClient;
use LemurHttpClient\Auth\BearerAuth;
$client = new HttpClient([
    'auth' => new BearerAuth('TOKEN_AQUI')
]);
$response = $client->get('https://api.ejemplo.com/protegido');
```

---

## 7. Uso de interceptors (middleware)

```php
use LemurHttpClient\HttpClient;
use LemurHttpClient\InterceptorPipeline;
$pipeline = new InterceptorPipeline();
$pipeline->addRequestInterceptor(function($req) {
    return $req->withHeader('X-Custom', '123');
});
$client = new HttpClient(['pipeline' => $pipeline]);
$response = $client->get('https://httpbin.org/headers');
echo $response->getBody();
```

---

## 8. Reintentos automáticos

```php
use LemurHttpClient\HttpClient;
use LemurHttpClient\RetryHandler;
$retry = new RetryHandler(5, 500); // 5 intentos, 500ms entre intentos
$client = new HttpClient(['retry' => $retry]);
$response = $client->get('https://httpbin.org/status/500');
```

---

## 9. Uso de caché

```php
use LemurHttpClient\HttpClient;
use LemurHttpClient\Cache\ArrayCache;
$cache = new ArrayCache();
$client = new HttpClient(['cache' => $cache]);
$response = $client->get('https://jsonplaceholder.typicode.com/posts/1');
```

---

## 10. Requests concurrentes

```php
use LemurHttpClient\HttpClient;
use LemurHttpClient\RequestBuilder;
$client = new HttpClient();
$reqs = [
    RequestBuilder::build('GET', 'https://jsonplaceholder.typicode.com/posts/1'),
    RequestBuilder::build('GET', 'https://jsonplaceholder.typicode.com/posts/2')
];
$responses = $client->all($reqs);
foreach ($responses as $res) {
    echo $res->getStatus() . "\n";
}
```

---

## 11. Cookies

```php
use LemurHttpClient\HttpClient;
use LemurHttpClient\Cookies;
$cookies = new Cookies();
$cookies->set('sessionid', 'abc123');
$client = new HttpClient(['cookies' => $cookies]);
$response = $client->get('https://httpbin.org/cookies');
```

---

## 12. Multipart/form-data

```php
use LemurHttpClient\HttpClient;
use LemurHttpClient\MultipartBuilder;
$builder = new MultipartBuilder();
$builder->addField('foo', 'bar')->addFile('file', '/ruta/al/archivo.txt');
$client = new HttpClient();
$response = $client->post('https://httpbin.org/post', [
    'body' => $builder->build()
]);
```

---

## 13. Streaming de respuesta

```php
use LemurHttpClient\HttpClient;
use LemurHttpClient\StreamHandler;
$client = new HttpClient(['stream' => new StreamHandler()]);
// Implementar lógica de streaming según necesidad
```
