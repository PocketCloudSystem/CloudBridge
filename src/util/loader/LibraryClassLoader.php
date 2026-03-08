<?php

namespace pocketcloud\cloud\bridge\util\loader;

use pmmp\thread\ThreadSafe;
use pmmp\thread\ThreadSafeArray;
use Symfony\Component\Filesystem\Path;

final class LibraryClassLoader extends ThreadSafe {

    private ThreadSafeArray $namespaces;

    public function __construct() {
        $this->namespaces = new ThreadSafeArray();
    }

    public function init(): void {
        spl_autoload_register($this->loadClass(...));
    }

    public function addPrefix(string $namespace, string $path): void {
        $this->namespaces->synchronized(function (string $namespace, string $path): void {
            if ($namespace === "") {
                $prefix = "";
            } else {
                $prefix = str_replace(["/", "\\", "\\\\", "//"], "\\", rtrim($namespace, "\\")) . "\\";
            }

            if (!isset($this->namespaces[$prefix])) $this->namespaces[$prefix] = new ThreadSafeArray();
            $this->namespaces[$prefix][] = $path;
        }, $namespace, $path);
    }

    public function loadClass(string $class): bool {
        if (($path = $this->findClass($class)) !== null) {
            include_once $path;
            if (!class_exists($class, false) && !trait_exists($class, false) && !interface_exists($class, false)) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function findClass(string $class): ?string {
        return $this->synchronized(function (string $class): ?string {
            $class = ltrim($class, "\\");
            $prefixes = iterator_to_array($this->namespaces);
            foreach ($prefixes as $prefix => $paths) {
                if ($prefix === "" || str_starts_with($class, $prefix)) {
                    $relative = $prefix === "" ?
                        str_replace("\\", "/", $class) . ".php" :
                        str_replace("\\", "/", substr($class, strlen($prefix))) . ".php";

                    foreach ($paths as $path) {
                        $file = Path::join($path, $relative);
                        if (is_file($file)) return $file;
                    }
                }
            }

            return null;
        }, $class);
    }
}