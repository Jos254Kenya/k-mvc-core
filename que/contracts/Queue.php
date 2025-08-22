<?php

declare(strict_types=1);

namespace sigawa\mvccore\que\contracts;

use sigawa\mvccore\que\payloads\JobPayload;

/** Contract for a queue system. */

/**
 * Contract for a queue backend (e.g., DB, Redis, SQS).
 */
interface Queue
{
    /**
     * Push a job payload immediately onto the queue.
     */
    public function push(JobPayload $payload, string $queue = 'default'): void;

    /**
     * Push a job payload with a delay in seconds.
     */
    public function later(int $delay, JobPayload $payload, string $queue = 'default'): void;

    /**
     * Pop one ready job payload (blocking or polling).
     */
    public function pop(string $queue = 'default'): ?JobPayload;

    /**
     * Mark a payload as permanently failed.
     */
    public function markFailed(JobPayload $payload, string $error): void;

    /**
     * Mark a payload as successfully completed.
     */
    public function markCompleted(JobPayload $payload): void;
}
