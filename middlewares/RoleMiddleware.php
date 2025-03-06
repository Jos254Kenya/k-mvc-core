<?php

namespace sigawa\mvccore\middlewares;

use sigawa\mvccore\Application;
use sigawa\mvccore\Request;
use sigawa\mvccore\Response;
use sigawa\mvccore\exception\ForbiddenException;

class RoleMiddleware extends BaseMiddleware
{
    private array $requiredRoles;
    private ?string $redirectUrl;

    /**
     * RoleMiddleware constructor.
     * @param array $roles Required roles to access the resource.
     * @param string|null $redirectUrl Optional URL to redirect unauthorized users.
     */
    public function __construct(array $roles, ?string $redirectUrl = null)
    {
        $this->requiredRoles = $roles;
        $this->redirectUrl = $redirectUrl;
    }

    public function execute(Request $request, Response $response)
    {
        $user = Application::$app->auth->user();
        
        if (!$user || !in_array($user->role, $this->requiredRoles)) {
            if ($this->redirectUrl) {
                $response->redirect($this->redirectUrl);
                return;
            }
            throw new ForbiddenException("You do not have permission to access this resource.");
        }
    }
}
