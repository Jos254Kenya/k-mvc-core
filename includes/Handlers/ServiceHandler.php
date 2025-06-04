<?php

namespace McConsole\Handlers;

use McConsole\Utils\Cli;

class ServiceHandler
{
    public static function handle(string $name): void
    {
        ['className' => $class, 'subDir' => $sub] = Cli::pathify($name);
        $namespace = rtrim(Cli::loadComposerNamespace(), '\\') . '\\services' . ($sub ? '\\' . str_replace('/', '\\', $sub) : '');
        $targetPath = "services" . ($sub ? "/$sub" : "");
        $filename = "$targetPath/{$class}Service.php";
        $stub = <<<PHP
<?php

namespace $namespace;

class {$class}Service
{
    public function example()
    {
        // Example method
    }
}
PHP;

        Cli::writeFile($filename, $stub);
        echo "✅ Service '{$class}Service' created.\n";
    }
}
