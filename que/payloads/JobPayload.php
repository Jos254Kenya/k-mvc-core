<?php
declare(strict_types=1);

namespace sigawa\mvccore\que\payloads;

/**
 * Immutable payload representing a queued job or event listener.
 *
 * - `type` distinguishes between a Job class and a queued Listener invocation.
 * - `data` holds serialized job/event state.
 * - `availableAt` is a UNIX timestamp when the job becomes available.
 */
final class JobPayload
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,   // 'job' | 'listener'
        public readonly string $class,  // Job class or Listener FQN
        public readonly array $data,
        public readonly int $attempts = 0,
        public readonly ?string $batchId = null,
        public readonly string $queue = 'default',
        public readonly ?int $availableAt = null,
    ) {}

    /**
     * Increment attempts and return a new payload instance.
     */
    public function incrementAttempts(): self
    {
        return new self(
            id: $this->id,
            type: $this->type,
            class: $this->class,
            data: $this->data,
            attempts: $this->attempts + 1,
            batchId: $this->batchId,
            queue: $this->queue,
            availableAt: $this->availableAt,
        );
    }

    /**
     * Check if the job is available for processing.
     */
    public function isAvailable(): bool
    {
        return $this->availableAt === null || $this->availableAt <= time();
    }
    /**
     * Return a new instance with a different availableAt timestamp.
     */
    public function withAvailableAt(int $availableAt): self
    {
        return new self(
            id: $this->id,
            type: $this->type,
            class: $this->class,
            data: $this->data,
            attempts: $this->attempts,
            batchId: $this->batchId,
            queue: $this->queue,
            availableAt: $availableAt,
        );
    }
    /**
     * Serialize payload into a storable array (for DB/Redis).
     */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'class'      => $this->class,
            'data'       => $this->data,
            'attempts'   => $this->attempts,
            'batchId'    => $this->batchId,
            'queue'      => $this->queue,
            'availableAt'=> $this->availableAt,
        ];
    }

    /**
     * Restore a JobPayload instance from a stored array.
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: $payload['id'],
            type: $payload['type'],
            class: $payload['class'],
            data: $payload['data'],
            attempts: $payload['attempts'] ?? 0,
            batchId: $payload['batchId'] ?? null,
            queue: $payload['queue'] ?? 'default',
            availableAt: $payload['availableAt'] ?? null,
        );
    }
}
