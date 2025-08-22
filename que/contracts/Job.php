<?php

declare(strict_types=1);

namespace sigawa\mvccore\que\contracts;


/**
 * A unit of work to be executed by the queue worker.
 *
 * All jobs must be idempotent (safe to run multiple times).
 */
interface Job
{
    /**
     * Execute the job.
     */
    public function handle(): void;

    /**
     * Max attempts before moving to failed jobs.
     */
    public function maxTries(): int;

    /**
     * Per-try timeout in seconds.
     */
    public function timeout(): int;

    /**
     * Exponential backoff base seconds (e.g., 5 → 5s, 10s, 20s…).
     */
    public function backoffBase(): int;
}
