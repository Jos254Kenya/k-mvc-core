<?php 

namespace sigawa\mvccore\db;
class AuditLog extends DbModel {
    public int $id =0;
    public string $action= '';
    public string $entity_type = '';
    public int $entity_id = 0;
    public ?int $user_id = null;
    public string $ip_address = '';
    public string $user_agent = '';
    public string $changes = '';
    public static function tableName():string
    {
        return 'audit_logs';
    }
    public function attributes():array
    {
        return ['action','entity_type','entity_id','user_id','ip_address','user_agent','changes'];
    }
}