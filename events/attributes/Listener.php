<?php
declare(strict_types=1);

namespace sigawa\mvccore\events\attributes;

use Attribute;

/**
 * Attach to a class (invokable) or a specific method.
 *
 * Example:
 * #[Listener(UserRegistered::class, priority: 50, queue: true)]
 * final class SendWelcomeEmail {
 *     public function __invoke(UserRegistered $event) { /..../ }
 * }
 *
 * Or:
 * final class AuditLogger {
 *     #[Listener(UserRegistered::class, priority: -10)]
 *     public function onUserRegistered(UserRegistered $event) {/.../ }
 * }
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Listener
{
    /**
     * @param class-string $event Fully-qualified event class name
     * @param int $priority Higher runs earlier (default 0)
     * @param bool $queue If true, will be queued when queue integration is enabled
     * @param string|null $method Optional method if used on a class attribute but handler is not __invoke
     */
    public function __construct(
        public string $event,
        public int $priority = 0,
        public bool $queue = false,
        public ?string $method = null,
    ) {}
}
