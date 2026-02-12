<?php

namespace pocketcloud\cloud\bridge\module;

use InvalidArgumentException;
use LogicException;
use pocketcloud\cloud\bridge\api\cache\InGameModuleCache;
use pocketcloud\cloud\bridge\module\impl\hubCommand\HubCommandModule;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use RuntimeException;

final class ModuleManager {
    use SingletonTrait;

    public const array DEFAULT_MODULES = [InGameModuleCache::HUB_COMMAND_MODULE, InGameModuleCache::SIGN_MODULE, InGameModuleCache::NPC_MODULE];

    /** @var array<Module> */
    private array $modules = [];

    public function __construct() {
        self::setInstance($this);
        $this->register(new HubCommandModule());
    }

    public function load(): void {
        foreach ($this->modules as $module) {
            $this->enable($module);
        }
    }

    public function enable(Module $module): void {
        if ($module->getModuleState() === ModuleState::NONE || $module->getModuleState() === ModuleState::DISABLED) {
            $module->onLoad();
            $module->setModuleState(ModuleState::ENABLED);
        }
    }

    public function disable(Module $module): void {
        if ($module->getModuleState() === ModuleState::ENABLED) {
            $module->setModuleState(ModuleState::DISABLED);
        }
    }

    public function disableAll(): void {
        foreach ($this->modules as $module) {
            $this->disable($module);
        }
    }

    public function tick(): void {
        foreach ($this->getEnabledModules() as $module) {
            $module->onTick(Server::getInstance()->getTick());
        }
    }

    public function register(Module $module): void {
        if (isset($this->modules[$module->getName()])) throw new LogicException("Module '{$module->getName()}' is already registered");
        $this->modules[$module->getName()] = $module;
    }

    public function unregister(Module|string $module): void {
        $module = $module instanceof Module ? $module : $this->modules[$module] ?? null;
        if ($module === null) throw new InvalidArgumentException("Module not registered");
        if (in_array($module->getName(), self::DEFAULT_MODULES)) throw new RuntimeException("Module '{$module->getName()}' cannot be unregistered");
        unset($this->modules[$module->getName()]);
    }

    public function get(string $name): ?Module {
        return $this->modules[$name] ?? null;
    }

    /** @return array<Module> */
    public function getEnabledModules(): array {
        return array_filter($this->modules, fn(Module $module) => $module->isEnabled());
    }

    /** @return array<Module> */
    public function getDisabledModules(): array {
        return array_filter($this->modules, fn(Module $module) => $module->isDisabled());
    }

    public function getAll(): array {
        return $this->modules;
    }
}