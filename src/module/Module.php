<?php

namespace pocketcloud\cloud\bridge\module;

use LogicException;

abstract class Module {

    private ModuleState $moduleState;

    public function __construct(
        private readonly string $name,
        private readonly string $description
    ) {
        $this->moduleState = ModuleState::DISABLED;
    }

    public function onLoad(): void {}

    public function onEnable(): void {}

    public function onDisable(): void {}

    public function onTick(int $currentTick): void {}

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
            default: throw new LogicException("Other module states are not supported and cannot be handled");
        }
    }

    final public function getName(): string {
        return $this->name;
    }

    final public function getDescription(): string {
        return $this->description;
    }

    public function getModuleState(): ModuleState {
        return $this->moduleState;
    }

    public function isEnabled(): bool {
        return $this->moduleState === ModuleState::ENABLED;
    }

    public function isDisabled(): bool {
        return $this->moduleState === ModuleState::DISABLED;
    }
}