<?php 
namespace sigawa\mvccore\middlewares;

use sigawa\mvccore\Request;
use sigawa\mvccore\Response;
use sigawa\mvccore\services\TenantServiceLoader;

class SubscriptionCheckMiddleware extends BaseMiddleware
{
    public function execute(Request $request, Response $response)
    {
        $token = $request->getHeader('Authorization');
        $token = str_replace('Bearer ', '', $token);

        $loader = new TenantServiceLoader($token);
        $plan = $loader->getTenantPlan();

        // Subscription expired
        if (strtotime($plan['end_date']) < time()) {
            $response->json(['error' => 'Your subscription has expired.'], 403);
        }

        // Optional: check if feature is allowed
        $features = json_decode($plan['features'], true);
        $requiredFeature = $request->getAttribute('requiredFeature') ?? null;

        if ($requiredFeature && (!isset($features[$requiredFeature]) || !$features[$requiredFeature])) {
            $response->json(['error' => "Feature '{$requiredFeature}' not available in your plan."], 403);
        }
    }
}
