<?php
declare(strict_types=1);

namespace sigawa\mvccore\events;
/**
 * Marker interface for domain events.
 * 
 * Any class implementing this interface is considered an Event.
 * 
 * Example:
 * final class UserRegistered implements Event {
 *     public function __construct(public string $userId) {}
 * }
 */
interface Event
{
    // No methods, just a contract for type safety.
}
