<?php

namespace sigawa\mvccore;

class AuthProvider
{
    private static ?UserModel $user = null; // Cached user instance

    public static function user(): ?UserModel
    {
        if (self::$user === null) {
            $userId = Application::$app->session->get('user');
            $sessionToken = Application::$app->session->get('session_token');
            $isApiRequest = !empty(getallheaders()['Authorization']); // Check if API request

            // If session token is missing, check Authorization header
            if (!$sessionToken && $isApiRequest) {
                $headers = getallheaders();
                if (!empty($headers['Authorization']) && preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
                    $sessionToken = $matches[1];
                }
            }

            if ($sessionToken) {
                $user = Application::$app->userClass::findOne(['session_token' => $sessionToken]);

                if ($user) {
                    self::$user = $user;
                } elseif ($userId && !$isApiRequest) {
                    // Only log out session-based users, not API users
                    self::logout();
                }
            }
        }
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

        error_log("Validating Token: " . $token);

        if (!$token) {
            error_log("Token is missing!");
            return false;
        }
        $user = Application::$app->userClass::findOne(['session_token' => $token]);

        if ($user) {
            error_log("User found: " . json_encode($user));
            self::$user = $user; // Set authenticated user
            return true;
        }

        error_log("No user found with this token.");
        return false;
    }


    public static function logout()
    {
        if (self::$user) {
            self::$user->update(['session_token' => null]); // Clear session token from DB
        }
        self::$user = null;
        Application::$app->session->remove('user');
        Application::$app->session->remove('session_token');
    }

    public static function setUser(UserModel $user, bool $generateNewToken = true)
    {
        if ($generateNewToken) {
            $user->session_token = bin2hex(random_bytes(32)); // Generate a secure token only if needed
            $user->update(['session_token' => $user->session_token]); // Save to DB
        }
        self::$user = $user;
        Application::$app->session->set('user', $user->id);
        Application::$app->session->set('session_token', $user->session_token);
    }
}
