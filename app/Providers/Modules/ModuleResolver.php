<?php

namespace App\Providers\Modules;

use RuntimeException;

trait ModuleResolver
{
    public string $modulesDirectory = __DIR__
    . DIRECTORY_SEPARATOR . '..'
    . DIRECTORY_SEPARATOR . '..'
    . DIRECTORY_SEPARATOR . '..'
    . DIRECTORY_SEPARATOR . 'modules'
    . DIRECTORY_SEPARATOR;

    public function resolveModuleName():string
    {
        $class = static::class;

        $namespace = substr($class, 0, strrpos($class, '\\'));

        $parts = explode('\\', $namespace);

        if (isset($parts[1])) {
            return $parts[1];
        }

        throw new RuntimeException(
            "Module name could not be resolved from class namespace: $namespace"
        );
    }

    public function resolveModulePath(string $path, string $module): string {
        $module = ucfirst($module);

        if (!is_dir("$this->modulesDirectory/$module")) {
            throw new RuntimeException("Module directory does not exist: $this->modulesDirectory/$module");
        }

        return "$this->modulesDirectory/$module/$path";
    }
}
