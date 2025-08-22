CREATE TABLE IF NOT EXISTS jobs (
    id VARCHAR(26) PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    class VARCHAR(255) NOT NULL,
    data JSON NOT NULL,
    attempts INT NOT NULL DEFAULT 0,
    batch_id VARCHAR(26) NULL,
    queue VARCHAR(100) NOT NULL DEFAULT 'default',
    available_at INT NOT NULL,
    reserved_at INT NULL,
    failed_at INT NULL,
    error TEXT NULL
);
