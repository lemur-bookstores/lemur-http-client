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
 * Token for cancelling concurrent requests.
 *
 * Allows signalling cancellation and providing a reason.
 *
 * @package  LemurHttpClient
 * @since    1.0.0
 */
class CancelToken
{
    private bool $cancelled = false;
    private ?string $reason = null;

    /**
     * Cancels the token with an optional reason.
     *
     * @param string|null $reason Reason for cancellation.
     * @return void
     * @since 1.0.0
     */
    public function cancel(string $reason = null): void
    {
        $this->cancelled = true;
        $this->reason = $reason;
    }

    /**
     * Checks if the token has been cancelled.
     *
     * @return bool True if cancelled.
     * @since 1.0.0
     */
    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    /**
     * Gets the cancellation reason, if any.
     *
     * @return string|null Reason for cancellation.
     * @since 1.0.0
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }
}
