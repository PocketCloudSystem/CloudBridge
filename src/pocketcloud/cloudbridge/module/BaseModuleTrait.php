<?php

namespace pocketcloud\cloudbridge\module;

use pocketcloud\cloudbridge\CloudBridge;

trait BaseModuleTrait {

    private static bool $enabled = false;

    public function getLogger(): \Logger {
        return \GlobalLogger::get();
    }

    public function getPlugin(): CloudBridge {
        return CloudBridge::getInstance();
    }

    public static function setEnabled(bool $enabled): void {
        self::$enabled = $enabled;
    }

    public static function isEnabled(): bool {
        return static::$enabled;
    }
}