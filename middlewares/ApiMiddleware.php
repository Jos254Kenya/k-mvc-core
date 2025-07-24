<?php

namespace sigawa\mvccore\middlewares;

use sigawa\mvccore\Request;
use sigawa\mvccore\Response;

class ApiMiddleware extends BaseMiddleware
{
    protected array $validTokens = [];
    protected bool $enforceJson = true;
    protected bool $enforceAuth = true;
    protected bool $enableRateLimit = false;
    protected int $rateLimit = 100;
    protected int $rateWindow = 60;

    protected static array $rateTracker = [];

    public function __construct(array $options = [])
    {
        $this->validTokens     = $options['tokens']           ?? ['Bearer dev-token'];
        $this->enforceJson     = $options['enforceJson']      ?? true;
        $this->enforceAuth     = $options['enforceAuth']      ?? true;
        $this->enableRateLimit = $options['enableRateLimit']  ?? false;
        $this->rateLimit       = $options['rateLimit']        ?? 100;
        $this->rateWindow      = $options['rateWindow']       ?? 60;
    }

    public function execute(Request $request, Response $response): void
    {
        // Enforce JSON format
        if ($this->enforceJson && !$this->isJsonRequest($request)) {
            $response->badRequest(['error' => 'Request must be JSON (Accept or Content-Type = application/json)']);
        }

        // Enforce API token
        if ($this->enforceAuth) {
            $token = $this->extractBearerToken($request->getHeader('Authorization'));
            if (!$this->isValidToken($token)) {
                $response->unauthorized(['error' => 'Invalid or missing API token']);
            }
        }

        // Apply rate limiting
        if ($this->enableRateLimit) {
            $key = $this->getRateLimitKey($request);
            if ($this->isRateLimited($key)) {
                $response->json(['error' => 'Too Many Requests'], 429);
            }
        }
    }

    protected function isJsonRequest(Request $request): bool
    {
        $accept = $request->getHeader('Accept') ?? '';
        $contentType = $request->getHeader('Content-Type') ?? '';
        return str_contains($accept, 'application/json') || str_contains($contentType, 'application/json');
    }

    protected function extractBearerToken(?string $header): ?string
    {
        return str_starts_with($header, 'Bearer ') ? trim(substr($header, 7)) : null;
    }

    protected function isValidToken(?string $token): bool
    {
        return in_array($token, $this->validTokens, true);
    }

    protected function getRateLimitKey(Request $request): string
    {
        return $request->getHeader('Authorization') ?: $request->getIp();
    }

    protected function isRateLimited(string $key): bool
    {
        $now = time();
        if (!isset(self::$rateTracker[$key])) {
            self::$rateTracker[$key] = [];
        }

        // Keep only recent timestamps within the rate window
        self::$rateTracker[$key] = array_filter(
            self::$rateTracker[$key],
            fn($timestamp) => $now - $timestamp < $this->rateWindow
        );

        // Enforce limit
        if (count(self::$rateTracker[$key]) >= $this->rateLimit) {
            return true;
        }

        self::$rateTracker[$key][] = $now;
        return false;
    }
}
