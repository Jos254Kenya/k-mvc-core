<?php

namespace sigawa\mvccore\middlewares;

use sigawa\mvccore\AuthProvider;
use sigawa\mvccore\Request;
use sigawa\mvccore\Response;

class NavigationMiddleware extends BaseMiddleware
{
    protected array $protectedRoutes;
    protected string $loginPage;

    /**
     * NavigationMiddleware constructor.
     * 
     * @param array $protectedRoutes List of routes that require authentication.
     * @param string $loginPage URL to redirect unauthenticated users.
     */
    public function __construct(array $protectedRoutes = [], string $loginPage = '/login')
    {
        $this->protectedRoutes = $protectedRoutes;
        $this->loginPage = $loginPage;
    }

    public function execute(Request $request, Response $response)
    {
        $path = $request->getPath();

        // Check if the current route is protected
        if (in_array($path, $this->protectedRoutes) && !AuthProvider::check()) {
            $response->redirect($this->loginPage);
        }
    }
}
