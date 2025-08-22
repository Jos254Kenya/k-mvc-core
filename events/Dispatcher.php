<?php

declare(strict_types=1);

namespace Sigawa\Events;

use sigawa\mvccore\events\Event;
use sigawa\mvccore\events\ListenerProvider;
use sigawa\mvccore\events\StoppableEvent;
use sigawa\mvccore\que\contracts\Queue;
use sigawa\mvccore\que\jobs\QueueableListenerJob;
use sigawa\mvccore\que\JobSerializer;

/**
 * PSR-14-like Event Dispatcher with optional queue integration.
 */
/**
 * PSR-14-like Event Dispatcher with strict queue integration.
 *
 * - Sync listeners are executed immediately.
 * - Queued listeners are serialized into JobPayloads and pushed to the queue.
 */
final class Dispatcher
{
    private readonly ?Queue $queue;
    private readonly ?JobSerializer $serializer;

    public function __construct(
        private readonly ListenerProvider $provider,
        ?Queue $queue = null,
        ?JobSerializer $serializer = null,
    ) {
        if ($queue !== null && $serializer === null) {
            throw new \InvalidArgumentException(
                'A JobSerializer must be provided when using a Queue in Dispatcher.'
            );
        }

        $this->queue = $queue;
        $this->serializer = $serializer;
    }

    public function dispatch(Event $event): void
    {
        $listeners = $this->provider->getListenersFor($event);

        foreach ($listeners as $meta) {
            $shouldQueue = ($meta['queue'] ?? false) === true && $this->queue !== null;

            if ($shouldQueue) {
                // Build a QueueableListenerJob and serialize to JobPayload
                $job = QueueableListenerJob::fromListener($meta['callable'], $event);

                $payload = $this->serializer->makeJob(
                    $job,
                    queue: $meta['queue_name'] ?? 'default'
                );

                $this->queue->push($payload);
                continue;
            }

            // Run synchronously
            ($meta['callable'])($event);

            // Stop propagation if requested
            if ($event instanceof StoppableEvent && $event->isPropagationStopped()) {
                break;
            }
        }
    }
}
