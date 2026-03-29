<?php
namespace LemurHttpClient;

/**
 * Adaptador para ejecutar múltiples requests concurrentes usando cURL multi
 */
class CurlMultiAdapter
{
    /**
     * Ejecuta múltiples requests en paralelo
     * @param Request[] $requests
     * @return Response[]
     */
    public function sendAll(array $requests): array
    {
        $multi = curl_multi_init();
        $handles = [];
        $responses = [];
        foreach ($requests as $key => $request) {
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
            foreach ($request->getOptions() as $k => $v) {
                if (defined($k)) {
                    curl_setopt($ch, constant($k), $v);
                }
            }
            curl_multi_add_handle($multi, $ch);
            $handles[$key] = $ch;
        }
        // Ejecutar todas
        $running = null;
        do {
            curl_multi_exec($multi, $running);
            curl_multi_select($multi);
        } while ($running > 0);
        // Recoger respuestas
        foreach ($handles as $key => $ch) {
            $body = curl_multi_getcontent($ch);
            $info = curl_getinfo($ch);
            $headerSize = $info['header_size'] ?? 0;
            $rawHeaders = substr($body, 0, $headerSize);
            $rawBody = substr($body, $headerSize);
            $headersArr = $this->parseHeaders($rawHeaders);
            $status = $info['http_code'] ?? 0;
            $responses[$key] = new Response($status, $headersArr, $rawBody, $info);
            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }
        curl_multi_close($multi);
        return $responses;
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
