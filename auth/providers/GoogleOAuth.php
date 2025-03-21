<?php 

namespace sigawa\mvccore\auth\providers;

use sigawa\mvccore\auth\OAuthService;

class GoogleOAuth implements OAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;
    private string $authUrl = "https://accounts.google.com/o/oauth2/auth";
    private string $tokenUrl = "https://oauth2.googleapis.com/token";
    private string $userInfoUrl = "https://www.googleapis.com/oauth2/v2/userinfo";

    public function __construct()
    {
        $this->clientId = $_ENV['GOOGLE_CLIENT_ID'];
        $this->clientSecret = $_ENV['GOOGLE_CLIENT_SECRET'];
        $this->redirectUri = $_ENV['GOOGLE_REDIRECT_URI'];
    }

    public function getAuthUrl(): string
    {
        $params = [
            "client_id" => $this->clientId,
            "redirect_uri" => $this->redirectUri,
            "response_type" => "code",
            "scope" => "email profile",
            "access_type" => "offline",
            "prompt" => "consent"
        ];
        return $this->authUrl . "?" . http_build_query($params);
    }

    public function getAccessToken(string $code): array
    {
        $data = [
            "code" => $code,
            "client_id" => $this->clientId,
            "client_secret" => $this->clientSecret,
            "redirect_uri" => $this->redirectUri,
            "grant_type" => "authorization_code"
        ];

        $ch = curl_init($this->tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function getUserData(string $accessToken): array
    {
        $ch = curl_init($this->userInfoUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $accessToken"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}