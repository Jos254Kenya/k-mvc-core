<?php

namespace sigawa\mvccore\middlewares;

use sigawa\mvccore\Application;
use sigawa\mvccore\AuthProvider;
use sigawa\mvccore\exception\ForbiddenException;
use sigawa\mvccore\Request;
use sigawa\mvccore\Response;

class PermissionMiddleware extends BaseMiddleware
{
    protected array $permissions;
    protected array $protectedRoutes; // Route-based permission protection

    public function __construct(array $permissions, array $protectedRoutes = [])
    {
        $this->permissions = $permissions;
        $this->protectedRoutes = $protectedRoutes;
    }

    public function execute(Request $request, Response $response)
    {
        if (!AuthProvider::check()) {
            return $response->redirect('/login');
        }

        $user = AuthProvider::user();
        $userPermissions = $user->getPermissions() ?? [];
        $path = $request->getPath();
        
        // 🔹 Route-based permission check (Throw ForbiddenException)
        if (isset($this->protectedRoutes[$path])) {
            $requiredPermissionForRoute = $this->protectedRoutes[$path];

            if (!isset($userPermissions[$requiredPermissionForRoute]) || $userPermissions[$requiredPermissionForRoute] !== 'Yes') {
                throw new ForbiddenException("Forbidden! You do not have permission to access this page.",401);
            }
        }

        // 🔹 Action-based permission check (Return JSON response)
        $controller = Application::$app->controller;
        $action = $controller->action ?? null; // Get the current action

        if ($action && isset($this->permissions[$action])) {
            $requiredPermissions = $this->permissions[$action];

            foreach ($requiredPermissions as $permission) {
                if (!isset($userPermissions[$permission]) || $userPermissions[$permission] !== 'Yes') {
                    return $response->json([
                        'success' => false,
                        'error' => "You do not have the right permissions to perform this action(s)",
                        'required' => $requiredPermissions,
                        'userPermissions' => $userPermissions
                    ], 403);
                }
            }
        }
    }
}
