<?php 
declare(strict_types=1);
namespace sigawa\mvccore\que;

use Closure;
use InvalidArgumentException;
use ReflectionFunction;
use sigawa\mvccore\events\Event;
use sigawa\mvccore\que\jobs\QueueableListenerJob;
use sigawa\mvccore\que\Payloads\JobPayload;
use sigawa\mvccore\support\Str;

class JobSerializer
{
    /** Convert a Job-like object to a payload. */
    public function makeJob(object $job, ?string $batchId = null, string $queue = 'default'): JobPayload
    {
        $class = $job::class;

        if (!method_exists($job, 'toArray')) {
            throw new InvalidArgumentException("Job {$class} must define toArray(): array");
        }

        return new JobPayload(
            id: Str::ulid(),
            type: 'job',
            class: $class,
            data: $job->toArray(),
            attempts: 0,
            batchId: $batchId,
            queue: $queue,
            availableAt: time(),
        );
    }

    /**
     * Serialize a listener invocation (listener + Event) into a payload,
     * wrapped as a QueueableListenerJob.
     */
    public function makeListenerInvocation(
        callable $listener,
        Event $event,
        ?string $batchId = null,
        string $queue = 'default'
    ): JobPayload {
        $job = QueueableListenerJob::fromListener($listener, $event);

        return new JobPayload(
            id: Str::ulid(),
            type: 'listener',
            class: QueueableListenerJob::class,
            data: $job->toArray(),
            attempts: 0,
            batchId: $batchId,
            queue: $queue,
            availableAt: time(),
        );
    }

    /**
     * Rebuild a job object from a payload.
     */
    public function restore(JobPayload $payload): object
    {
        if (!class_exists($payload->class)) {
            throw new InvalidArgumentException("Job class {$payload->class} not found.");
        }

        $class = $payload->class;

        if (method_exists($class, 'fromArray')) {
            /** @phpstan-ignore-next-line */
            return $class::fromArray($payload->data);
        }

        // Fall back: try constructor hydration
        return new $class(...$payload->data);
    }

    /**
     * Convert any callable to a string for reference (debugging only).
     */
    private function callableToString(callable $listener): string
    {
        if ($listener instanceof Closure) {
            $ref = new ReflectionFunction($listener);
            return 'Closure@' . ($ref->getName() ?: spl_object_hash($listener));
        }

        if (is_array($listener)) {
            if (is_object($listener[0])) {
                return $listener[0]::class . '@' . $listener[1];
            }
            return $listener[0] . '@' . $listener[1];
        }

        if (is_string($listener)) {
            return $listener;
        }

        if (is_object($listener) && method_exists($listener, '__invoke')) {
            return $listener::class . '@__invoke';
        }

        return 'UnknownCallable';
    }
}
