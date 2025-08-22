<?php

declare(strict_types=1);

namespace sigawa\mvccore\que;

use sigawa\mvccore\que\contracts\Queue;
use sigawa\mvccore\que\payloads\JobPayload;
use Throwable;

/**
 * Background queue worker.
 *
 * Usage:
 *   $worker = new Worker($queue, $serializer);
 *   $worker->run('default', 5); // process 'default' queue with 5s sleep when idle
 */
/**
 * Background queue worker.
 *
 * Usage:
 *   $worker = new Worker($queue, $serializer);
 *   $worker->run('default', 5); // process 'default' queue with 5s sleep when idle
 */
final class Worker
{
    public function __construct(
        private readonly Queue $queue,
        private readonly JobSerializer $serializer,
    ) {}

    /**
     * Run the worker loop for a given queue.
     */
    public function run(string $queue = 'default', int $sleepSeconds = 5): void
    {
        while (true) {
            $payload = $this->queue->pop($queue);

            if ($payload === null) {
                sleep($sleepSeconds); // idle wait
                continue;
            }

            $this->process($payload, $queue);
        }
    }

    /**
     * Process a single JobPayload.
     */
    private function process(JobPayload $payload, string $queue): void
    {
        try {
            $job = $this->serializer->restore($payload);

            if (!method_exists($job, 'handle')) {
                throw new \RuntimeException("Job {$payload->class} must implement handle().");
            }

            $job->handle();

            $this->queue->markCompleted($payload);
        } catch (Throwable $e) {
            $this->handleFailure($payload, $queue, $e);
        }
    }

    /**
     * Handle a job failure (retry or mark failed).
     */
    private function handleFailure(JobPayload $payload, string $queue, Throwable $e): void
    {
        $job = null;

        try {
            $job = $this->serializer->restore($payload);
        } catch (Throwable) {
            // Ignore deserialization issues (already a hard failure)
        }

        $maxAttempts = $job?->maxTries() ?? 3;
        $backoffBase = $job?->backoffBase() ?? 5;

        if ($payload->attempts < $maxAttempts) {
            $retryPayload = $payload->incrementAttempts();

            // compute exponential backoff delay
            $delay = $backoffBase * (2 ** ($payload->attempts - 1));

            $this->queue->later($delay, $retryPayload, $queue);
            return;
        }

        // Give up → mark failed
        $this->queue->markFailed($payload, $e->getMessage());
        $this->logFailure($payload, $e);
    }

    private function logFailure(JobPayload $payload, Throwable $e): void
    {
        error_log("Job {$payload->id} ({$payload->class}) failed permanently: " . $e->getMessage());
    }
}
