<?php

namespace pocketcloud\cloud\bridge\module;

use AttachableLogger;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketmine\Server;

abstract class Module {

    private ModuleState $moduleState;

    public function __construct(
        private readonly string $name,
        private readonly string $description
    ) {
        $this->moduleState = ModuleState::DISABLED;
    }

    public function onLoad(): void {}

    public function onTick(int $currentTick): void {}

    final public function getName(): string {
        return $this->name;
    }

    final public function getDescription(): string {
        return $this->description;
    }

    public function getModuleState(): ModuleState {
        return $this->moduleState;
    }

    public function setModuleState(ModuleState $moduleState): void {
        $this->moduleState = $moduleState;
        switch ($moduleState) {
            case ModuleState::ENABLED: {
                $this->onEnable();
                break;
            }
            case ModuleState::DISABLED: {
                $this->onDisable();
                break;
            }
        }
    }

    public function onEnable(): void {}

    public function onDisable(): void {}

    public function isEnabled(): bool {
        return $this->moduleState === ModuleState::ENABLED;
    }

    public function isDisabled(): bool {
        return $this->moduleState === ModuleState::DISABLED;
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

    public static function get(): static {
        return ModuleManager::getInstance()->get(static::class);
    }
}