<?php

namespace sigawa\mvccore\services;

use sigawa\mvccore\db\AuditLog;

class AuditLoggerService
{
    public static function log(string $action, string $entityType, int $entityId, ?int $userId = null, array $changes = []): void
    {
        $log = new AuditLog();
        $log->loadData([
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'changes' => json_encode($changes),
        ]);
        $log->save();
    }
}
