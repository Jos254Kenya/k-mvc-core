<?php

namespace sigawa\mvccore\middlewares;

use sigawa\mvccore\Application;
use sigawa\mvccore\AuthProvider;
use sigawa\mvccore\exception\UnauthorizedException;
use sigawa\mvccore\Request;
use sigawa\mvccore\Response;
use sigawa\mvccore\exception\ForbiddenException;

class AuthMiddleware extends BaseMiddleware
{
    protected array $actions = [];
    protected bool $isApi = false;
    protected ?string $redirectUrl = null;
    protected array $allowedRoles = [];

    /**
     * AuthMiddleware constructor.
     *
     * @param array $actions List of actions where this middleware applies.
     * @param string|null $redirectUrl URL to redirect unauthenticated users (optional).
     * @param array $allowedRoles Optional roles that can access the resource.
     * @param bool $isApi Whether this is an API request.
     */
    public function __construct(array $actions = [], ?string $redirectUrl = null, array $allowedRoles = [], bool $isApi = false)
    {
        $this->actions = $actions;
        $this->redirectUrl = $redirectUrl;
        $this->allowedRoles = $allowedRoles;
        $this->isApi = $isApi;
    }

    /**
     * Execute the middleware.
     *
     * @param Request $request
     * @param Response $response
     * @throws ForbiddenException|UnauthorizedException
     */
    public function execute(Request $request, Response $response)
    {
        if (!$this->shouldApplyMiddleware()) {
            return; // Middleware should not apply to this request
        }

        // Extract token for authentication
        $token = $this->extractTokenFromHeader($request);

        if ($token && AuthProvider::validateToken($token)) {
            $user = AuthProvider::user();
            if ($user) {
                AuthProvider::setUser($user, false); // Do not generate a new token
            }
        }


        // Role-based access control (if roles are defined)
        if (!empty($this->allowedRoles)) {
            $user = AuthProvider::user();
            if (!$user || !in_array($user->role ?? 'guest', $this->allowedRoles)) {
                throw new ForbiddenException("You do not have permission to access this resource.");
            }
        }
    }

    /**
     * Determine if middleware should apply to the current action.
     *
     * @return bool
     */
    private function shouldApplyMiddleware(): bool
    {
        if (empty($this->actions)) {
            return true; // Apply middleware to all actions
        }

        $controller = Application::$app->controller;
        return $controller && in_array($controller->action, $this->actions);
    }

    /**
     * Extract Bearer token from Authorization header.
     *
     * @param Request $request
     * @return string|null
     */
    private function extractTokenFromHeader(Request $request): ?string
    {
        $authHeader = $request->getHeader('Authorization');
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return null;
        }
        return $matches[1];
    }

    /**
     * Handle unauthorized access.
     *
     * @param Response $response
     * @throws ForbiddenException|UnauthorizedException
     */
    private function handleUnauthorized(Response $response)
    {
        error_log("Unauthorized request. API mode: " . ($this->isApi ? 'true' : 'false'));

        if ($this->isApi) {
            throw new UnauthorizedException("Invalid or missing authentication token.");
        }

        if ($this->redirectUrl) {
            $response->redirect($this->redirectUrl);
            return;
        }

        throw new ForbiddenException("You must be logged in to access this resource.");
    }
}
