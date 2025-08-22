<?php 
declare(strict_types=1);

namespace sigawa\mvccore\que;

use sigawa\mvccore\que\contracts\Queue;
use sigawa\mvccore\que\payloads\JobPayload;

/**
 * Redis-backed queue.
 *
 * Uses:
 *   - List per queue: "queues:{name}"
 *   - Hash per job:   "jobs:{id}"
 */
final class RedisQueue implements Queue
{
    public function __construct(
        private readonly Redis $redis
    ) {}

    public function push(JobPayload $payload, string $queue = 'default'): void
    {
        $key = $this->queueKey($queue);

        // Store job metadata separately
        $this->redis->hMSet($this->jobKey($payload->id), [
            'id'          => $payload->id,
            'type'        => $payload->type,
            'class'       => $payload->class,
            'data'        => json_encode($payload->data, JSON_THROW_ON_ERROR),
            'attempts'    => (string)$payload->attempts,
            'batch_id'    => $payload->batchId ?? '',
            'queue'       => $queue,
            'available_at'=> (string)($payload->availableAt ?? time()),
        ]);

        // Push job ID onto queue list
        $this->redis->lPush($key, $payload->id);
    }

    public function later(int $delay, JobPayload $payload, string $queue = 'default'): void
    {
        $payload = $payload->withAvailableAt(time() + $delay);
        $this->push($payload, $queue);
    }

    public function pop(string $queue = 'default'): ?JobPayload
    {
        $key = $this->queueKey($queue);

        // Block until a job is available (timeout = 5s for graceful exit handling)
        $result = $this->redis->brPop([$key], 5);

        if (!$result) {
            return null;
        }

        [$list, $jobId] = $result;

        $raw = $this->redis->hGetAll($this->jobKey($jobId));
        if (!$raw) {
            return null; // job lost (shouldn't happen)
        }

        // Check availability timestamp
        if ((int)$raw['available_at'] > time()) {
            // Not ready yet → requeue
            $this->redis->lPush($key, $jobId);
            return null;
        }

        return JobPayload::fromArray([
            'id'          => $raw['id'],
            'type'        => $raw['type'],
            'class'       => $raw['class'],
            'data'        => json_decode($raw['data'], true, 512, JSON_THROW_ON_ERROR),
            'attempts'    => (int)$raw['attempts'],
            'batch_id'    => $raw['batch_id'] ?: null,
            'queue'       => $raw['queue'],
            'available_at'=> (int)$raw['available_at'],
        ]);
    }

    public function markFailed(JobPayload $payload, string $error): void
    {
        $this->redis->hMSet($this->jobKey($payload->id), [
            'failed_at' => (string)time(),
            'error'     => $error,
        ]);
    }

    public function markCompleted(JobPayload $payload): void
    {
        // Remove metadata + key cleanup
        $this->redis->del($this->jobKey($payload->id));
    }

    private function queueKey(string $queue): string
    {
        return "queues:{$queue}";
    }

    private function jobKey(string $id): string
    {
        return "jobs:{$id}";
    }
}