<?php
declare(strict_types=1);

namespace sigawa\mvccore\events;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use sigawa\mvccore\events\attributes\Listener as ListenerAttr;

final class ListenerProvider
{
    /** @var array<class-string, array<int, array{priority:int, queue:bool, callable:callable, id:string}>> */
    private array $map = [];

    /**
     * Register a listener callable for an event class.
     *
     * @param class-string<Event> $eventClass
     */
    public function register(string $eventClass, callable $listener, int $priority = 0, bool $queue = false, ?string $id = null): void
    {
        $this->map[$eventClass][] = [
            'priority' => $priority,
            'queue'    => $queue,
            'callable' => $listener,
            'id'       => $id ?? spl_object_hash((object)[$eventClass, $priority, $queue, $listener]),
        ];
    }

    /**
     * Attribute auto-discovery: pass a list of class names to scan.
     * Typically you’ll pass get_declared_classes() after Composer autoload.
     *
     * @param iterable<class-string> $classes
     */
    public function discover(iterable $classes): void
    {
        foreach ($classes as $class) {
            if (!class_exists($class)) continue;

            $rc = new ReflectionClass($class);

            // Class-level attributes
            foreach ($rc->getAttributes(ListenerAttr::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                /** @var ListenerAttr $meta */
                $meta = $attr->newInstance();
                $method = $meta->method ?? '__invoke';
                if (!$rc->hasMethod($method)) {
                    throw new \LogicException(sprintf(
                        'Listener %s declares method "%s" but it does not exist.', $class, $method
                    ));
                }
                $this->register(
                    $meta->event,
                    // Defer instantiation to call-time to support containers later
                    $this->wrap($class, $method),
                    $meta->priority,
                    $meta->queue,
                    $class.'::'.$method
                );
            }
            
            // Method-level attributes
            foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC) as $rm) {
                foreach ($rm->getAttributes(ListenerAttr::class, ReflectionAttribute::IS_INSTANCEOF) as $attr) {
                    /** @var ListenerAttr $meta */
                    $meta = $attr->newInstance();
                    $this->register(
                        $meta->event,
                        $this->wrap($class, $rm->getName()),
                        $meta->priority,
                        $meta->queue,
                        $class.'::'.$rm->getName()
                    );
                }
            }
        }

        // Normalize: sort by priority desc for each event
        foreach ($this->map as $event => &$listeners) {
            usort($listeners, static fn($a, $b) => $b['priority'] <=> $a['priority']);
        }
    }

    /**
     * @return list<array{priority:int, queue:bool, callable:callable, id:string}>
     */
    public function getListenersFor(Event $event): array
    {
        return $this->map[$event::class] ?? [];
    }

    /** Create a lazy callable on [new Class, method] (DI hook point later). */
    private function wrap(string $class, string $method): callable
    {
        return static function (Event $event) use ($class, $method): void {
            // In the future, resolve $class from Sigawa container
            $instance = new $class();
            $instance->{$method}($event);
        };
    }
}
