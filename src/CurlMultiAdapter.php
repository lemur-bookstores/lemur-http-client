<?php
/**
 * @package    LemurHttpClient
 * @category   Adapter
 * @author     [elkincp Chaverra]
 * @copyright  [2026] [lemur-bookstores]
 * @license    MIT
 * @since      1.0.0
 */

namespace LemurHttpClient;

/**
 * Sends multiple HTTP requests concurrently using cURL multi interface.
 *
 * Builds and executes multiple cURL handles, collects responses in order,
 * and handles individual errors per request.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class CurlMultiAdapter
{
    private CurlHandleBuilder $builder;

    /**
     * CurlMultiAdapter constructor.
     *
     * @param CurlHandleBuilder|null $builder Optional handle builder for testing or customization.
     * @since 1.0.0
     */
    public function __construct(CurlHandleBuilder $builder = null)
    {
        $this->builder = $builder ?? new CurlHandleBuilder();
    }

    /**
     * Sends multiple HTTP requests concurrently and returns their responses.
     *
     * Each request is built and added to the cURL multi handle. Responses are
     * collected in the same order as the input array. If a request fails, a
     * Response with status 0 and a 'Curl-Error' header is returned for that entry.
     *
     * @param  Request[] $requests  Array of Request objects to send.
     * @return Response[]           Array of Response objects in the same order.
     * @since  1.0.0
     */
    public function sendAll(array $requests): array
    {
        $multi   = curl_multi_init();
        $handles = [];

        // Build all handles using the shared builder
        foreach ($requests as $key => $request) {
            $ch = $this->builder->build($request);
            curl_multi_add_handle($multi, $ch);
            $handles[$key] = $ch;
        }

        // Execute without busy-wait
        do {
            $status = curl_multi_exec($multi, $running);
            if ($running) {
                curl_multi_select($multi);
            }
        } while ($running > 0 && $status === CURLM_OK);

        // Collect responses in the same order, handling individual errors
        $responses = [];
        foreach ($handles as $key => $ch) {
            $output = curl_multi_getcontent($ch);
            $error  = curl_error($ch);
            if ($error !== '') {
                // Return a Response with status 0 and error in headers
                $responses[$key] = new Response(0, ['Curl-Error' => $error], '', curl_getinfo($ch));
            } else {
                $responses[$key] = $this->builder->parseResponse($ch, $output);
            }
            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }

        curl_multi_close($multi);
        return $responses;
    }
}