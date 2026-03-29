# lemur-http-client

> Framework HTTP modular, inspirado en Axios, para PHP 7.4+, sin dependencias externas. Soporta concurrencia, interceptors, autenticación, caché, cookies, multipart y streaming.

## Índice

1. [Introducción](#introducción)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Estructura de Carpetas y Componentes](#estructura-de-carpetas-y-componentes)
4. [Instalación y Configuración](#instalación-y-configuración)
5. [Guía de Uso](#guía-de-uso)
6. [Integraciones y APIs](#integraciones-y-apis)
7. [Desarrollo y Contribución](#desarrollo-y-contribución)
8. [Preguntas Frecuentes (FAQ)](#preguntas-frecuentes-faq)
9. [Ejemplos de Uso](docs/04-examples.md)
10. [Licencia y Créditos](#licencia-y-créditos)

---

## Introducción

LemurHttpClient es un framework HTTP para PHP que combina la compatibilidad universal de cURL con una API fluida y moderna. Permite requests concurrentes, interceptors, autenticación, caché, cookies, multipart y streaming, todo sin dependencias externas.

## Arquitectura del Sistema

- Modular, extensible y desacoplado
- Inspirado en Axios y PSR-4
- [Ver detalles en docs/01-arquitectur.md](docs/01-arquitectur.md)

## Estructura de Carpetas y Componentes

- `src/` — Código fuente principal
- `docs/` — Documentación técnica y ejemplos
- [Ver detalles en docs/02-components.md](docs/02-components.md)

## Instalación y Configuración

1. Clona el repositorio y ejecuta `composer install`.
2. Configura tus componentes (auth, cache, retry, pipeline, cookies, etc.) en el constructor de `HttpClient`.

## Guía de Uso

- Ver [docs/04-examples.md](docs/04-examples.md) para ejemplos completos.
- Soporta todos los métodos HTTP, requests concurrentes, interceptors, autenticación, caché, cookies, multipart y streaming.

## Integraciones y APIs

- Compatible con cualquier API RESTful
- Ejemplos de integración avanzada en [docs/04-examples.md](docs/04-examples.md)

## Desarrollo y Contribución

- Código limpio, PSR-4, sin dependencias externas
- Pull requests y sugerencias bienvenidas

## Preguntas Frecuentes (FAQ)

- Ver [docs/06-faq.md](docs/06-faq.md)

## Licencia y Créditos

- Licencia: MIT
- Autores: Equipo Lemur
