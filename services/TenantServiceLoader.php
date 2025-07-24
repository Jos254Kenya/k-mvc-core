<?php 
namespace sigawa\mvccore\services;

class TenantServiceLoader
{
    protected $centralDb;
    protected $tenantDb;
    protected $tenant;

    public function __construct($apiToken)
    {
        $this->centralDb = $this->connectCentral();
        $this->tenant = $this->fetchTenantByToken($apiToken);
        $this->tenantDb = $this->connectTenant($this->tenant['db_name']);
    }

    protected function connectCentral()
    {
        // Connect to central DB
        return new \PDO($_ENV['CENTRAL_DB_DSN'], $_ENV['CENTRAL_DB_USER'], $_ENV['CENTRAL_DB_PASS']);
    }

    protected function connectTenant($dbName)
    {
        $dsn = "mysql:host=localhost;dbname=$dbName;charset=utf8mb4";
        return new \PDO($dsn, $_ENV['TENANT_DB_USER'], $_ENV['TENANT_DB_PASS']);
    }

    protected function fetchTenantByToken($token)
    {
        $stmt = $this->centralDb->prepare("SELECT * FROM tenants WHERE api_token = ?");
        $stmt->execute([$token]);
        $tenant = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$tenant || $tenant['status'] !== 'active') {
            throw new \Exception("Unauthorized or inactive tenant.");
        }

        return $tenant;
    }

    public function getTenantPlan()
    {
        $stmt = $this->centralDb->prepare("
            SELECT p.*, s.start_date, s.end_date, s.status AS subscription_status
            FROM plans p
            JOIN subscriptions s ON s.plan_id = p.id
            WHERE s.tenant_id = ?
            ORDER BY s.end_date DESC
            LIMIT 1
        ");
        $stmt->execute([$this->tenant['id']]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getTenantDb()
    {
        return $this->tenantDb;
    }

    public function getTenant()
    {
        return $this->tenant;
    }
}
