<?php
namespace LemurHttpClient;

/**
 * Adaptador simple para ejecutar una petición HTTP usando cURL
 */
class CurlAdapter
{
    public function send(Request $request): Response
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request->getUrl());
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->getMethod());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = [];
        foreach ($request->getHeaders() as $k => $v) {
            $headers[] = "$k: $v";
        }
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($request->getBody() !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $request->getBody());
        }
        // Opciones adicionales
        foreach ($request->getOptions() as $k => $v) {
            if (defined($k)) {
                curl_setopt($ch, constant($k), $v);
            }
        }
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        $headerSize = $info['header_size'] ?? 0;
        $rawHeaders = substr($body, 0, $headerSize);
        $rawBody = substr($body, $headerSize);
        $headersArr = $this->parseHeaders($rawHeaders);
        $status = $info['http_code'] ?? 0;
        curl_close($ch);
        return new Response($status, $headersArr, $rawBody, $info);
    }

    private function parseHeaders($raw): array
    {
        $headers = [];
        $lines = explode("\r\n", $raw);
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                [$k, $v] = explode(':', $line, 2);
                $headers[trim($k)] = trim($v);
            }
        }
        return $headers;
    }
}
