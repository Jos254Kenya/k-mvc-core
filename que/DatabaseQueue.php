<?php 
declare(strict_types=1);

namespace sigawa\mvccore\que;

use PDO;
use sigawa\mvccore\que\contracts\Queue;
use sigawa\mvccore\que\payloads\JobPayload;

/**
 * Database-backed queue implementation (MySQL/Postgres/SQLite).
 *
 * Requirements:
 * - `jobs` table migration
 * - PDO connection passed in
 */
final class DatabaseQueue implements Queue
{
    public function __construct(
        private readonly PDO $pdo
    ) {}

    public function push(JobPayload $payload, string $queue = 'default'): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO jobs (id, type, class, data, attempts, batch_id, queue, available_at)
            VALUES (:id, :type, :class, :data, :attempts, :batch_id, :queue, :available_at)
        ");

        $stmt->execute([
            ':id'          => $payload->id,
            ':type'        => $payload->type,
            ':class'       => $payload->class,
            ':data'        => json_encode($payload->data, JSON_THROW_ON_ERROR),
            ':attempts'    => $payload->attempts,
            ':batch_id'    => $payload->batchId,
            ':queue'       => $queue,
            ':available_at'=> $payload->availableAt ?? time(),
        ]);
    }

    public function later(int $delay, JobPayload $payload, string $queue = 'default'): void
    {
        $availableAt = time() + $delay;
        $delayed = $payload->withAvailableAt($availableAt);

        $this->push($delayed, $queue);
    }

    public function pop(string $queue = 'default'): ?JobPayload
    {
        $this->pdo->beginTransaction();

        // Select one ready job (availableAt <= now, not reserved, not failed)
        $stmt = $this->pdo->prepare("SELECT * FROM jobs
            WHERE queue = :queue
              AND available_at <= :now
              AND reserved_at IS NULL
              AND failed_at IS NULL
            ORDER BY available_at ASC
            LIMIT 1
            FOR UPDATE SKIP LOCKED");

        $stmt->execute([
            ':queue' => $queue,
            ':now'   => time(),
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $this->pdo->commit();
            return null;
        }

        // Mark reserved
        $reserve = $this->pdo->prepare("UPDATE jobs SET reserved_at = :now WHERE id = :id");
        $reserve->execute([
            ':now' => time(),
            ':id'  => $row['id'],
        ]);

        $this->pdo->commit();

        return JobPayload::fromArray($row);
    }

    public function markFailed(JobPayload $payload, string $error): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE jobs SET failed_at = :now, error = :error WHERE id = :id
        ");
        $stmt->execute([
            ':now'   => time(),
            ':error' => $error,
            ':id'    => $payload->id,
        ]);
    }

    public function markCompleted(JobPayload $payload): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM jobs WHERE id = :id");
        $stmt->execute([':id' => $payload->id]);
    }
}