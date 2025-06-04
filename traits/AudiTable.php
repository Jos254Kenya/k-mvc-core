<?php 
namespace sigawa\mvccore\traits;

use sigawa\mvccore\Application;
use sigawa\mvccore\services\AuditLoggerService;

trait Auditable
{
    public function logAction(string $action, ?array $changes = null): void
    {
        AuditLoggerService::log(
            $action,
            static::class,
            $this->getPrimaryKeyValue(),
            Application::$app->user->id ?? null,
            $changes ??[]
        );
    }
}
