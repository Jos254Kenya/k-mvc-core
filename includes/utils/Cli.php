<?php
// includes/Utils/Cli.php

namespace McConsole\Utils;

class Cli
{
    /**
     * Parses command-line arguments and options
     *
     * @param array $argv
     * @return array
     */
    public static function parse(array $argv): array
    {
        $command = $argv[1] ?? null;
        $name = $argv[2] ?? null;

        // Extract options like --flag=value or --flag
        $options = [];
        foreach ($argv as $arg) {
            if (str_starts_with($arg, '--')) {
                $parts = explode('=', substr($arg, 2), 2);
                $options[$parts[0]] = $parts[1] ?? true;
            }
        }

        return [
            'command' => $command,
            'name' => $name,
            'options' => $options,
        ];
    }

    /**
     * Ensure directory exists, create if it doesn’t
     */
    public static function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Format a class name into file path, respecting subdirectories
     */
    public static function pathify(string $name): array
    {
        $pathParts = explode('/', str_replace('\\', '/', $name));
        $className = array_pop($pathParts);
        $subDir = implode('/', $pathParts);

        return [
            'className' => $className,
            'subDir' => $subDir,
        ];
    }
    // includes/Utils/Cli.php

    // Add this method inside the Cli class
    public static function loadComposerNamespace(): string
    {
        $composerFile = BASE_PATH . '/composer.json';
        if (!file_exists($composerFile)) {
            exit("❌ composer.json not found in project root\n");
        }

        $composer = json_decode(file_get_contents($composerFile), true);
        return key($composer['autoload']['psr-4'] ?? []);
    }
   public static function writeFile(string $relativePath, string $content): void
{
    // If $relativePath is already absolute, use it directly
    $fullPath = preg_match('/^[A-Z]:\\\\|^\//i', $relativePath) ? $relativePath : BASE_PATH . '/' . $relativePath;

    $dir = dirname($fullPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    file_put_contents($fullPath, $content);
}

}
