<?php

namespace McConsole\Handlers;

use McConsole\Utils\Cli;

class ModelHandler
{
    public static function handle(string $name): void
    {
        ['className' => $class, 'subDir' => $sub] = Cli::pathify($name);
         $baseDir = getcwd(); 
        $namespace = Cli::loadComposerNamespace() . 'models' . ($sub ? '\\' . str_replace('/', '\\', $sub) : '');
        $targetPath = $baseDir . "/models" . ($sub ? "/$sub" : "");
        // $targetPath = "models" . ($sub ? "/$sub" : "");
        $filename = "$targetPath/{$class}.php";

        $stub = <<<PHP
<?php

namespace $namespace;

use sigawa\\mvccore\\db\\DbModel;

class $class extends DbModel
{
    public static function tableName(): string { return strtolower('$class'); }
    public function attributes(): array { return []; }
    public function labels(): array { return []; }
    public function rules() { return []; }
}
PHP;

        Cli::writeFile($filename, $stub);
        echo "✅ Model '$class' created.\n";
    }
}
