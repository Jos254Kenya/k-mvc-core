<?php
namespace McConsole\Handlers;

use McConsole\Utils\Cli;

class ProjectHandler
{
    public static function handle(string $name): void
    {
        $structure = [
            'controllers',
            'models/users',
            'services',
            'helpers',
            'views/layouts',
            'routes',
            'public',
            'runtime',
            'vendor',
        ];

        foreach ($structure as $dir) {
            Cli::ensureDirectory("$name/$dir");
        }

        // Create .env file
        file_put_contents("$name/.env", <<<ENV
DB_DSN=mysql:host=localhost;dbname=$name
DB_USER=root
DB_PASSWORD=
ENV
        );

        // Create User model
        $userModel = <<<PHP
<?php

namespace Karibuwebdev\\Jazakapu\\models\\users;

use sigawa\\mvccore\\UserModel;

class User extends UserModel
{
    protected string \$table = 'users';
    protected string \$primaryKey = 'id';
    // Define fillables, hidden, etc.
}
PHP;
        file_put_contents("$name/models/users/User.php", $userModel);

        // Create view layout
        file_put_contents("$name/views/layout/main.php", <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to $name</title>
</head>
<body>
    <h1>Welcome to $name</h1>
</body>
</html>
HTML);

        // Create routes/load.php
        file_put_contents("$name/routes/load.php", <<<PHP
<?php

use sigawa\\mvccore\\Router;

function loadRoutesFromDirectory(string \$directory, Router \$router)
{
    \$files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(\$directory)
    );

    foreach (\$files as \$file) {
        if (\$file->isFile() && \$file->getExtension() === 'php' && \$file->getBasename() !== 'load.php') {
            require \$file->getPathname(); // These files will access \$router
        }
    }
}

// Expose \$router to all route files
\$router = \$GLOBALS['app']->router ?? throw new Exception("Router not available");
// Load all route files and pass router to them
loadRoutesFromDirectory(__DIR__, \$router);
PHP);
// Create routes/web.php with a simple demo route
file_put_contents("$name/routes/web.php", <<<PHP
<?php

// Example route using the router
\$router->get('/', function() {
    echo "Welcome to the $name homepage!";
});
PHP);
        // Create full public/index.php
        file_put_contents("$name/public/index.php", <<<PHP
<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use sigawa\\mvccore\\Application;
use Karibuwebdev\\Jazakapu\\models\\users\\User;

\$dotenv = Dotenv::createImmutable(dirname(__DIR__));
\$dotenv->load();

\$config = [
    'userClass' => User::class,
    'db' => [
        'dsn' => \$_ENV['DB_DSN'],
        'user' => \$_ENV['DB_USER'],
        'password' => \$_ENV['DB_PASSWORD'],
    ]
];

try {
    \$app = new Application(dirname(__DIR__), \$config);
    require_once __DIR__ . '/../routes/load.php';
    \$app->run();
} catch (\\Exception \$th) {
    echo "Database connection error: Please try again later or contact support for help";
}
PHP);

        echo "✅ Project '$name' initialized successfully with routing, index, and default User model.\n";
    }
}