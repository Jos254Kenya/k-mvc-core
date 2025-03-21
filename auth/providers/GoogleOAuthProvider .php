<?php

namespace sigawa\mvccore\auth\providers;


use sigawa\mvccore\Application;
use sigawa\mvccore\auth\OAuthProviderInterface;

class GoogleOAuthProvider implements OAuthProviderInterface
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        $this->clientId = getenv('GOOGLE_CLIENT_ID');
        $this->clientSecret = getenv('GOOGLE_CLIENT_SECRET');
        $this->redirectUri = getenv('GOOGLE_REDIRECT_URI');
    }

    public function getAuthUrl(): string
    {
        return "https://accounts.google.com/o/oauth2/auth?" . http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'scope'         => 'email profile',
            'access_type'   => 'offline'
        ]);
    }

    public function getAccessToken(string $code): ?array
    {
        $response = $this->makeRequest('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'grant_type'    => 'authorization_code'
        ]);

        return $response ? json_decode($response, true) : null;
    }

    public function getUserData(string $accessToken): ?array
    {
        $response = $this->makeRequest("https://www.googleapis.com/oauth2/v1/userinfo?alt=json", [], [
            "Authorization: Bearer $accessToken"
        ]);

        return $response ? json_decode($response, true) : null;
    }

    private function makeRequest(string $url, array $postFields = [], array $headers = []): ?string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        if (!empty($postFields)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        curl_close($ch);
        return $response ?: null;
    }
}
