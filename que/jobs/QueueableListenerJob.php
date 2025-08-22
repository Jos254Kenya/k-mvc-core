<?php 
declare(strict_types=1);

namespace sigawa\mvccore\que\jobs;
use InvalidArgumentException;
use sigawa\mvccore\events\Event;

class QueueableListenerJob
{
    public function __construct(
        public string $listenerClass,   // e.g. "App\Listeners\SendWelcomeEmail"
        public string $listenerMethod,  // e.g. "handle"
        public string $eventClass,      // e.g. "App\Events\UserRegistered"
        public array $eventData = []    // Event data serialized
    ) {}

    /**
     * Factory helper to build from listener + event.
     */
    public static function fromListener(callable $listener, Event $event): self
    {
        if (is_array($listener)) {
            [$target, $method] = $listener;

            if (is_object($target)) {
                $listenerClass = $target::class;
            } elseif (is_string($target)) {
                $listenerClass = $target;
            } else {
                throw new InvalidArgumentException("Unsupported listener type.");
            }

            $listenerMethod = $method;
        } elseif (is_string($listener)) {
            // e.g. "App\Listeners\Foo@handle" style
            if (str_contains($listener, '@')) {
                [$listenerClass, $listenerMethod] = explode('@', $listener, 2);
            } elseif (str_contains($listener, '::')) {
                [$listenerClass, $listenerMethod] = explode('::', $listener, 2);
            } else {
                $listenerClass = $listener;
                $listenerMethod = '__invoke';
            }
        } elseif (is_object($listener) && method_exists($listener, '__invoke')) {
            $listenerClass  = $listener::class;
            $listenerMethod = '__invoke';
        } else {
            throw new InvalidArgumentException("Unsupported listener format for queueable job.");
        }

        if (!method_exists($event, 'toArray')) {
            throw new InvalidArgumentException("Event  must define toArray().");
        }

        return new self(
            listenerClass: $listenerClass,
            listenerMethod: $listenerMethod,
            eventClass: $event::class,
            eventData: $event->toArray(),
        );
    }

    /**
     * Restore the event instance from serialized form.
     */
    public function event(): Event
    {
        if (!class_exists($this->eventClass)) {
            throw new InvalidArgumentException("Event class {$this->eventClass} not found.");
        }

        $eventClass = $this->eventClass;

        if (method_exists($eventClass, 'fromArray')) {
            /** @phpstan-ignore-next-line */
            return $eventClass::fromArray($this->eventData);
        }

        // Try constructor parameter hydration
        return new $eventClass(...$this->eventData);
    }

    /**
     * Execute the job (called by the worker).
     */
    public function handle(): void
    {
        if (!class_exists($this->listenerClass)) {
            throw new InvalidArgumentException("Listener class {$this->listenerClass} not found.");
        }

        $listener = new ($this->listenerClass)();

        if (!method_exists($listener, $this->listenerMethod)) {
            throw new InvalidArgumentException("Method {$this->listenerClass}::{$this->listenerMethod} not found.");
        }

        $listener->{$this->listenerMethod}($this->event());
    }

    /**
     * Convert to array for JobSerializer.
     */
    public function toArray(): array
    {
        return [
            'listenerClass'  => $this->listenerClass,
            'listenerMethod' => $this->listenerMethod,
            'eventClass'     => $this->eventClass,
            'eventData'      => $this->eventData,
        ];
    }

    /**
     * Rehydrate from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['listenerClass'],
            $data['listenerMethod'],
            $data['eventClass'],
            $data['eventData'] ?? []
        );
    }
}