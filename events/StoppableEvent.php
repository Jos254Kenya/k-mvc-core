<?php
declare(strict_types=1);

namespace sigawa\mvccore\events;

/** Optional: implement for events that can stop propagation. */
interface StoppableEvent
{
    public function isPropagationStopped(): bool;
    public function stopPropagation(): void;
}
// This trait can be used to implement the StoppableEvent interface.
// It provides the basic functionality to track and control event propagation.
/**
 *  StopsPropagation trait
 *  * This trait provides methods to check if propagation is stopped
 *  * and to stop propagation of the event.
 */
trait StopsPropagation
{
    private bool $propagationStopped = false;

    public function isPropagationStopped(): bool { return $this->propagationStopped; }
    public function stopPropagation(): void { $this->propagationStopped = true; }
}
