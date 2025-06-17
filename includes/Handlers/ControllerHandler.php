<?php

namespace McConsole\Handlers;

use McConsole\Utils\Cli;

class ControllerHandler
{
    public static function handle(string $name, array $options): void
    {
        ['className' => $class, 'subDir' => $sub] = Cli::pathify($name);
        $baseDir = getcwd(); // <-- The user's current project directory
        $namespace = Cli::loadComposerNamespace() . 'controllers' . ($sub ? '\\' . str_replace('/', '\\', $sub) : '');
        // $targetPath = "controllers" . ($sub ? "/$sub" : "");
        $targetPath = $baseDir . "/controllers" . ($sub ? "/$sub" : "");
        $filename = "$targetPath/{$class}Controller.php";

        $methods = $options['resource'] ?? false
            ? self::resourceMethods()
            : "    public function index(Request \$request, Response \$response) {}\n";

        $stub = <<<PHP
<?php

namespace $namespace;

use sigawa\\mvccore\\Request;
use sigawa\\mvccore\\Response;
use sigawa\\mvccore\\Controller;

class {$class}Controller extends Controller
{
$methods
}
PHP;

        Cli::writeFile($filename, $stub);
        echo "✅ Controller '{$class}Controller' created.\n";

        if (!empty($options['with-service'])) {
            ServiceHandler::handle($name);
        }
    }

    private static function resourceMethods(): string
    {
        return <<<PHP
    public function index(Request \$request, Response \$response) {}
    public function show(Request \$request, Response \$response, \$id) {}
    public function create(Request \$request, Response \$response) {}
    public function store(Request \$request, Response \$response) {}
    public function edit(Request \$request, Response \$response, \$id) {}
    public function update(Request \$request, Response \$response, \$id) {}
    public function destroy(Request \$request, Response \$response, \$id) {}
PHP;
    }
}
