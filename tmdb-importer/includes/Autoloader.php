<?php
namespace TMDBImporter\Core;

class Autoloader
{
    private static string $prefix = 'TMDBImporter\\';
    private static string $baseDir = '';

    public static function register(): void
    {
        self::$baseDir = dirname(__DIR__, 2) . '/';
        spl_autoload_register([self::class, 'loadClass'], true, true);
    }

    public static function loadClass(string $class): void
    {
        $len = strlen(self::$prefix);
        if (strncmp(self::$prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        $file = self::$baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
}