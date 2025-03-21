<?php

namespace sigawa\mvccore\auth;

interface OAuthProviderInterface
{
    public function getAuthUrl(): string;
    public function getAccessToken(string $code): ?array;
    public function getUserData(string $accessToken): ?array;
}
