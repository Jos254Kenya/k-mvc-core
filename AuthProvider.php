<?php

namespace sigawa\mvccore;

class AuthProvider
{
    private static ?UserModel $user = null; // Cached user instance

    public function __construct()
    {
        $this->loadUser();
    }

    private function loadUser()
    {
        $session = Application::$app->session;
        $defaultUserClass = Application::$app->userClass;
        $guestUserClass = Application::$app->guestClass;

        $authModel = $session->get('auth_model') ?? $defaultUserClass; // Use stored model or default

        if (!class_exists($authModel)) {
            return; // Prevent loading an invalid class
        }

        $sessionToken = $session->get('session_token');

        // First, try session token authentication
        if ($sessionToken) {
            $user = $authModel::findOne(['session_token' => $sessionToken]);
            if ($user) {
                self::$user = $user;
                return;
            }
        }

        // Fallback to userId-based authentication if token is missing or invalid
        $userId = $session->get('user') ?? $session->get('guest');
        if ($userId) {
            $user = $authModel::findOne([$authModel::primaryKey() => $userId]);
            if ($user) {
                self::$user = $user;
            } else {
                $this->clearSession(); // Use a helper method for better maintainability
            }
        }
    }

    public static function user(): ?UserModel
    {
        return self::$user;
    }

    public static function id(): ?int
    {
        return self::user()?->id ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function validateToken(string $token): bool
    {
        if (!$token) {
            error_log("AuthProvider: Token is missing!");
            return false;
        }

        $user = Application::$app->userClass::findOne(['session_token' => $token]);

        if ($user) {
            self::$user = $user; // Set authenticated user
            return true;
        }

        return false;
    }

    public static function logout()
    {
        if (self::$user) {
            self::$user->update(['session_token' => null]);
        }
        self::$user = null;
        self::clearSession();
    }

    private static function clearSession()
    {
        $session = Application::$app->session;
        $session->remove('user');
        $session->remove('session_token');
        $session->remove('auth_model');
        $session->remove('guest');
    }

    public static function hasPermission(string $permission): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        $permissions = $user->getPermissions(); // Fetch permissions from DB
        return in_array($permission, $permissions, true);
    }
    public static function setUser($user, bool $generateNewToken = true)
    {
        $guestClass = Application::$app->guestClass;

        if ($user instanceof $guestClass) {
            Application::$app->session->set('guest', true);
        } else {
            Application::$app->session->set('guest', false);
        }
        Application::$app->session->set('user', $user->id);
            if ($generateNewToken) {
            $user->session_token = bin2hex(random_bytes(32));
            Application::$app->session->set('session_token', $user->session_token);
            $user->update(['session_token' => $user->session_token]);

        }
        Application::$app->user = $user;
    }

    // public static function setUser(UserModel $user, bool $generateNewToken = true)
    // {
    //     $session = Application::$app->session;

    //     if ($generateNewToken) {
    //         $user->session_token = bin2hex(random_bytes(32));
    //         $user->update(['session_token' => $user->session_token]);
    //     }

    //     self::$user = $user;

    //     // **Security Improvement: Regenerate session ID on login**
    //     session_regenerate_id(true);

    //     $session->set('user', $user->id);
    //     $session->set('session_token', $user->session_token);
    //     $session->set('auth_model', get_class($user)); // Store model class
    // }
}
