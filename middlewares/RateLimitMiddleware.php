<?php
namespace sigawa\mvccore\middlewares;

use sigawa\mvccore\services\RedisService;
use sigawa\mvccore\Request;
use sigawa\mvccore\Response;
use sigawa\mvccore\AuthProvider;

class RateLimitMiddleware
{
    private RedisService $redis;
    private int $maxRequests;
    private int $window;

    public function __construct(RedisService $redis, int $maxRequests = 100, int $window = 3600)
    {
        $this->redis = $redis;
        $this->maxRequests = $maxRequests;
        $this->window = $window;
    }

    public function handle(Request $request, Response $response)
    {
        $identifier = AuthProvider::id() ?? $request->getIp(); // Use user ID if available
        $key = "api_rate:$identifier";

        $rateData = $this->redis->rateLimitWithInfo($key, $this->maxRequests, $this->window);

        if (!$rateData['allowed']) {
            return $response->json([
                'success' => false,
                'message' => 'Rate limit exceeded. Try again later.'
            ], 429);
        }

        // Add rate-limit headers
        header("X-RateLimit-Limit: {$this->maxRequests}");
        header("X-RateLimit-Remaining: {$rateData['remaining']}");
        header("X-RateLimit-Reset: {$rateData['resetTime']}");
    }
}
