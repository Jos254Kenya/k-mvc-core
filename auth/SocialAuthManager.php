<?php

namespace sigawa\mvccore\auth;

use sigawa\mvccore\Application;
use sigawa\mvccore\auth\providers\GoogleOAuthProvider;

class SocialAuthManager
{
    private static array $providers = [
        'google' => GoogleOAuthProvider::class
    ];

    public static function getProvider(string $providerName): ?OAuthProviderInterface
    {
        if (!isset(self::$providers[$providerName])) {
            return null;
        }
        return new self::$providers[$providerName]();
    }
}
