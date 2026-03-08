<?php

namespace pocketcloud\cloud\bridge\module;

use InvalidArgumentException;
use LogicException;
use pocketcloud\cloud\bridge\api\cache\InGameModuleCache;
use pocketcloud\cloud\bridge\module\impl\hubCommand\HubCommandModule;
use pocketcloud\cloud\bridge\module\impl\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\module\impl\sign\CloudSignModule;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use RuntimeException;

final class ModuleManager {
    use SingletonTrait;

    public const array DEFAULT_MODULES = [InGameModuleCache::HUB_COMMAND_MODULE, InGameModuleCache::SIGN_MODULE, InGameModuleCache::NPC_MODULE];

    /** @var array<class-string<Module>, Module> */
    private array $modules = [];

    public function __construct() {
        self::setInstance($this);
        $this->register(new HubCommandModule());
        $this->register(new CloudSignModule());
        $this->register(new CloudNpcModule());
    }

    public function register(Module $module): void {
        $this->modules[$module::class] = $module;
    }

    public function load(): void {
        foreach ($this->modules as $module) {
            if (InGameModuleCache::getModuleState($module->getName())) {
                $this->enable($module);
            }
        }
    }

    public function enable(Module $module): void {
        if ($module->getModuleState() === ModuleState::DISABLED) {
            $module->onLoad();
            $module->setModuleState(ModuleState::ENABLED);
        }
    }

    public function disableAll(): void {
        foreach ($this->modules as $module) {
            $this->disable($module);
        }
    }

    public function disable(Module $module): void {
        if ($module->getModuleState() === ModuleState::ENABLED) {
            $module->setModuleState(ModuleState::DISABLED);
        }
    }

    public function tick(): void {
        foreach ($this->getEnabledModules() as $module) {
            $module->onTick(Server::getInstance()->getTick());
        }
    }

    /** @return array<Module> */
    public function getEnabledModules(): array {
        return array_filter($this->modules, fn(Module $module) => $module->isEnabled());
    }

    public function unregister(Module|string $module): void {
        $module = $module instanceof Module ? $module : $this->get($module) ?? null;
        if ($module === null) throw new InvalidArgumentException("Module not registered");
        if (in_array($module->getName(), self::DEFAULT_MODULES)) throw new RuntimeException("Module '{$module->getName()}' cannot be unregistered");
        unset($this->modules[$module->getName()]);
    }

    public function get(string $name): ?Module {
        return $this->modules[$name] ?? array_find($this->modules, fn(Module $module) => $module->getName() == $name);
    }

    /** @return array<Module> */
    public function getDisabledModules(): array {
        return array_filter($this->modules, fn(Module $module) => $module->isDisabled());
    }

    public function getAll(): array {
        return $this->modules;
    }
}