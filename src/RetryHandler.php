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
 * Handler for automatic retries with backoff.
 *
 * Retries a callable up to a maximum number of attempts with delay between retries.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class RetryHandler
{
    private int $maxRetries;
    private int $delayMs;

    /**
     * RetryHandler constructor.
     *
     * @param int $maxRetries Maximum number of retries.
     * @param int $delayMs    Delay between retries in milliseconds.
     * @since 1.0.0
     */
    public function __construct(int $maxRetries = 3, int $delayMs = 200)
    {
        $this->maxRetries = $maxRetries;
        $this->delayMs = $delayMs;
    }

    /**
     * Executes a callable with retry logic.
     *
     * Retries the callable if an exception is thrown, up to maxRetries.
     *
     * @param callable $fn The function to execute.
     * @return mixed       The result of the callable.
     * @throws \Exception If all retries fail.
     * @since 1.0.0
     */
    public function handle(callable $fn)
    {
        $attempts = 0;
        do {
            try {
                return $fn();
            } catch (\Exception $e) {
                $attempts++;
                if ($attempts > $this->maxRetries) {
                    throw $e;
                }
                usleep($this->delayMs * 1000);
            }
        } while (true);
    }
}
