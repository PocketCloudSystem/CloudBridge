<?php

namespace pocketcloud\cloud\bridge\module;

use AttachableLogger;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketmine\Server;

abstract class BaseModule {

    private bool $enabled = false;

    abstract protected function onEnable(): void;

    abstract protected function onDisable(): void;

    public function setEnabled(bool $enabled = true): void {
        $this->enabled = $enabled;
        if ($enabled) $this->onEnable();
        else $this->onDisable();
    }

    public function getServer(): Server {
        return Server::getInstance();
    }

    public function getPlugin(): CloudBridge {
        return CloudBridge::getInstance();
    }

    public function getLogger(): AttachableLogger {
        return $this->getPlugin()->getLogger();
    }

    public function isEnabled(): bool {
        return $this->enabled;
    }

    public static function get(): static {
        return ModuleManager::getInstance()->getModule(static::class);
    }
}