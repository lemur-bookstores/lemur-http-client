<?php
namespace LemurHttpClient;

/**
 * Token para cancelar requests concurrentes
 */
class CancelToken
{
    private bool $cancelled = false;
    private ?string $reason = null;

    public function cancel(string $reason = null): void
    {
        $this->cancelled = true;
        $this->reason = $reason;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }
}
