<?php
/**
 * @package    LemurHttpClient
 * @category   Support
 * @author     [elkincp Chaverra]
 * @copyright  [2026] [lemur-bookstores]
 * @license    MIT
 * @since      1.0.0
 */

namespace LemurHttpClient;

/**
 * Handles streaming data from a resource.
 *
 * Reads data in chunks and invokes a callback for each chunk.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class StreamHandler
{
    /**
     * Reads a resource in chunks and calls the callback for each chunk.
     *
     * @param resource $resource  The resource to read from.
     * @param callable $onData    Callback to handle each chunk.
     * @return void
     * @since 1.0.0
     */
    public function handle($resource, callable $onData): void
    {
        while (!feof($resource)) {
            $chunk = fread($resource, 8192);
            if ($chunk === false) break;
            $onData($chunk);
        }
        fclose($resource);
    }
}
