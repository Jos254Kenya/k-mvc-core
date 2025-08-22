<?php

namespace sigawa\mvccore;

class Session
{
    protected const FLASH_KEY = 'flash_messages';
    protected int $lifetime;
    protected array $cookieParams;

    public function __construct()
    {
        // read from env with sensible defaults
        $this->lifetime = (int) ($_ENV['SESSION_LIFETIME'] ?? 1800); // seconds
        $cookieDomain = $_ENV['SESSION_COOKIE_DOMAIN'] ?? null; // e.g. ".example.com" or null for host-only
        $secure = $this->isHttps() || (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production');

        // When doing cross-origin cookies from browsers, SameSite=None + Secure is required.
        $sameSite = $_ENV['SESSION_SAMESITE'] ?? ($secure ? 'None' : 'Lax');

        // Build cookie params (PHP 7.3+ supports array form)
        $this->cookieParams = [
            'lifetime' => $this->lifetime,
            'path' => $_ENV['SESSION_COOKIE_PATH'] ?? '/',
            'domain' => $cookieDomain,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => "Lax",
        ];

        ini_set('session.gc_maxlifetime', (string) $this->lifetime);
        session_set_cookie_params($this->cookieParams);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Prepare flash messages lifecycle
        $flashMessages = $_SESSION[self::FLASH_KEY] ?? [];
        foreach ($flashMessages as $key => &$flashMessage) {
            $flashMessage['remove'] = true;
        }
        $_SESSION[self::FLASH_KEY] = $flashMessages;
    }

    private function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') return true;
        return false;
    }

    // Flash helpers
    public function setFlash(string $key, $message): void
    {
        $_SESSION[self::FLASH_KEY][$key] = [
            'remove' => false,
            'value' => $message
        ];
    }

    public function getFlash(string $key)
    {
        return $_SESSION[self::FLASH_KEY][$key]['value'] ?? false;
    }

    // Generic session accessors
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key)
    {
        return $_SESSION[$key] ?? false;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function regenerate(bool $deleteOld = true): void
    {
        // Call when authenticating to avoid fixation
        session_regenerate_id($deleteOld);
    }

    public function __destruct()
    {
        $this->removeFlashMessages();
    }

    private function removeFlashMessages(): void
    {
        $flashMessages = $_SESSION[self::FLASH_KEY] ?? [];
        foreach ($flashMessages as $key => $flashMessage) {
            if (!empty($flashMessage['remove'])) {
                unset($flashMessages[$key]);
            }
        }
        $_SESSION[self::FLASH_KEY] = $flashMessages;
    }
}
