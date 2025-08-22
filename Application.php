<?php

namespace sigawa\mvccore;

use sigawa\mvccore\db\Database;
use Dotenv\Dotenv;
use sigawa\mvccore\services\RedisService; // For environment configuration

class Application
{
    const EVENT_BEFORE_REQUEST = 'beforeRequest';
    const EVENT_AFTER_REQUEST = 'afterRequest';

    protected array $eventListeners = [];
    public static Application $app;
    public static string $ROOT_DIR;
    public string $userClass;
    public ?string $guestClass = null; // Allow dynamic guest user model
    public string $layout = 'main';
    public Router $router;
    public Request $request;
    public Response $response;
    public ?Controller $controller = null;
    public Database $db;
    public Session $session;
    public View $view;
    public ?UserModel $user;
    public AuthProvider $auth;

    public array $services = []; // Service container
    public RedisService $redis;
    public function __construct($rootDir, $config)
    {
        $this->user = null;
        $this->userClass = $config['userClass'];
        $this->userClass = $config['userClass']; // Default user model
        $this->guestClass = $config['guestClass'] ?? null; // Optional guest model
        self::$ROOT_DIR = $rootDir;
        self::$app = $this;

        $this->loadEnvironment($rootDir);

        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        $this->db = new Database($config['db']);
        $this->session = new Session();
        $this->view = new View();
        $this->redis = new RedisService();
        // Register default services

        $this->auth = new AuthProvider();
        $this->registerService('auth', $this->auth);
        $this->registerService('google_oauth', new \sigawa\mvccore\auth\providers\GoogleOAuth());
        // Register services
        foreach (['request', 'response', 'router', 'db', 'session', 'view', 'redis', 'auth'] as $service) {
            $this->registerService($service, $this->$service);
        }
        // Fetch user from AuthProvider
        $this->user = AuthProvider::user();
    }

    private function loadEnvironment($rootDir)
    {
        if (file_exists($rootDir . '/.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable($rootDir);
            $dotenv->load();
        }
    }

    public function guestLogin(UserModel $user)
    {
        $this->session->set('guest', $user->id);
        $this->session->set('auth_model', get_class($user)); // Store model class in session
        $this->user = $user;
        // AuthProvider::setUser($user);
        return true;
    }
    public function login(UserModel $user)
    {
        $this->session->set('user', $user->id);
        $this->session->set('auth_model', get_class($user)); // Store model class in session
        $this->user = $user;
        AuthProvider::setUser($user);
        return true;
    }

    public function logout()
    {
        AuthProvider::logout();
        $this->user = null;
    }

    public function logoutGuest()
    {
        $this->session->remove('guest');
        $this->session->remove('auth_model'); // Remove stored model
        $this->user = null;
    }

    public static function isGuest(): bool
    {
        return !AuthProvider::check();
    }

    public function run()
    {
        $this->triggerEvent(self::EVENT_BEFORE_REQUEST);
        try {
            $result = $this->router->resolve();

            if (is_array($result)) {
                header('Content-Type: application/json');
                echo json_encode($result);
            } else {
                echo $result;
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        } finally {
            $this->triggerEvent(self::EVENT_AFTER_REQUEST);
        }
    }


    private function handleException(\Exception $e)
    {
        $request = $this->request;
        $response = $this->response;

        // Determine if the request is an API request
        $isApiRequest = str_starts_with($request->getUrl(), '/api');

        // Normalize status code (avoid non-integer like SQLSTATE '42S22')
        $rawCode = $e->getCode();
        $statusCode = is_numeric($rawCode) && (int)$rawCode >= 100 && (int)$rawCode <= 599
            ? (int)$rawCode
            : 500;

        $response->statusCode($statusCode);

        if ($isApiRequest) {
            // Return JSON error response
            return $response->json([
                'error' => $e->getMessage(),
                'status' => $statusCode
            ], $statusCode);
        }
        // For web requests, render an error page
        echo $this->view->renderView('_error', ['exception' => $e]);
    }

    public function triggerEvent($eventName)
    {
        $callbacks = $this->eventListeners[$eventName] ?? [];
        foreach ($callbacks as $callback) {
            call_user_func($callback);
        }
    }

    public function on($eventName, $callback)
    {
        $this->eventListeners[$eventName][] = $callback;
    }

    public function registerService(string $name, $service)
    {
        $this->services[$name] = $service;
    }

    public function getService(string $name)
    {
        return $this->services[$name] ?? null;
    }
}
