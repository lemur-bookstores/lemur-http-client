<?php
namespace LemurHttpClient;

/**
 * Handler para reintentos automáticos con backoff
 */
class RetryHandler
{
    private int $maxRetries;
    private int $delayMs;

    public function __construct(int $maxRetries = 3, int $delayMs = 200)
    {
        $this->maxRetries = $maxRetries;
        $this->delayMs = $delayMs;
    }

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
