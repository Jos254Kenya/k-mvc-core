<?php
namespace sigawa\mvccore\constants;

use InvalidArgumentException;
use PDO;

class TokenGenerator
{
    private int $length;
    private int $expiry;
    private ?string $secretKey='';
    private ?PDO $db;
    private string $tableName;
    private array $columnMap;

    public function __construct(
        int $length = 32, 
        int $expiry = 900, 
        string $secretKey, 
        ?PDO $db = null, 
        string $tableName = 'tokens', 
        array $columnMap = []
    ) {
        $this->length = $length;
        $this->expiry = $expiry;
        $this->secretKey = $secretKey;
        $this->db = $db;
        $this->tableName = $tableName;

        // Default column mappings (customizable per use case)
        $this->columnMap = array_merge([
            'token' => 'token',
            'purpose' => 'purpose',
            'expires_at' => 'expires_at',
            'user_id' => 'user_id',
            'created_at' => 'created_at'
        ], $columnMap);
    }

    public function generateToken(string $purpose, bool $isNumeric = false, ?int $userId = null): array
    {
        $token = $isNumeric ? $this->generateOTP() : bin2hex(random_bytes($this->length / 2));
        $expiresAt = time() + $this->expiry;

        if ($this->secretKey) {
            $token = $this->signToken($token, $purpose);
        }

        if ($this->db) {
            $this->storeTokenInDB($token, $purpose, $expiresAt,$userId);
        }

        return [
            'token' => $token,
            'expires_at' => $expiresAt
        ];
    }
    public function generateTokenOTP(string $purpose, ?int $userId = null): array
    {
        $tokens  = $this->generateOTP();
        $expiresAt = time() + $this->expiry;

        // if ($this->secretKey) {
        //     $token = $this->signToken($tokens, $purpose);
        // }

        if ($this->db) {
            $this->storeTokenInDB($tokens, $purpose, $expiresAt,$userId);
        }

        return [
            'token' => $tokens,
            'expires_at' => $expiresAt
        ];
    }

    private function generateOTP(): string
    {
        return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function signToken(string $token, string $purpose): string
    {
        return hash_hmac('sha256', $token . $purpose, $this->secretKey);
    }

    private function storeTokenInDB(string $token, string $purpose, int $expiresAt, ?int $userId): void
    {
        // Ensure the userId is provided for the ON DUPLICATE KEY clause
        if ($userId === null) {
            throw new InvalidArgumentException('User ID must be provided for token storage.');
        }
    
        $query = "INSERT INTO {$this->tableName} 
            ({$this->columnMap['token']}, {$this->columnMap['purpose']}, {$this->columnMap['expires_at']}, {$this->columnMap['user_id']}) 
            VALUES (:token, :purpose, :expires_at, :user_id)
            ON DUPLICATE KEY UPDATE 
            {$this->columnMap['token']} = VALUES({$this->columnMap['token']}), 
            {$this->columnMap['purpose']} = VALUES({$this->columnMap['purpose']}), 
            {$this->columnMap['expires_at']} = VALUES({$this->columnMap['expires_at']})";
    
        $stmt = $this->db->prepare($query);
        $params = [
            ':token' => $token,
            ':purpose' => $purpose,
            ':expires_at' => $expiresAt,
            ':user_id' => $userId
        ];
    
        $stmt->execute($params);
    }
    

    public function verifyToken(string $token, string $purpose): bool
    {
        $stmt = $this->db->prepare("SELECT {$this->columnMap['expires_at']} FROM {$this->tableName} 
            WHERE {$this->columnMap['token']} = :token AND {$this->columnMap['purpose']} = :purpose");
        $stmt->execute([
            ':token' => $token,
            ':purpose' => $purpose
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result && time() < $result[$this->columnMap['expires_at']];
    }
}