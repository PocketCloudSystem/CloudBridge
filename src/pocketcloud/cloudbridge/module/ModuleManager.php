<?php

namespace pocketcloud\cloudbridge\module;

use pocketcloud\cloudbridge\module\globalchat\GlobalChatModule;
use pocketcloud\cloudbridge\module\hubcommand\HubCommandModule;
use pocketcloud\cloudbridge\module\npc\CloudNPCModule;
use pocketcloud\cloudbridge\module\sign\CloudSignModule;
use pocketcloud\cloudbridge\util\ModuleSettings;
use pocketmine\utils\SingletonTrait;

class ModuleManager {
    use SingletonTrait;

    private array $modules;

    public function __construct() {
        self::setInstance($this);

        $this->modules = [
            HubCommandModule::class => new HubCommandModule(),
            GlobalChatModule::class => new GlobalChatModule(),
            CloudNPCModule::class => new CloudNPCModule(),
            CloudSignModule::class => new CloudSignModule()
        ];
    }

    public function addModule(BaseModule $baseModule): void {
        $this->modules[$baseModule::class] = $baseModule;
    }

    public function removeModule(BaseModule $baseModule): void {
        if (isset($this->modules[$baseModule::class])) unset($this->modules[$baseModule::class]);
    }

    /** @internal */
    public function syncModuleStates(): void {
        HubCommandModule::get()->setEnabled(ModuleSettings::isHubCommandModuleEnabled());
        GlobalChatModule::get()->setEnabled(ModuleSettings::isGlobalChatModuleEnabled());
        CloudNPCModule::get()->setEnabled(ModuleSettings::isNpcModuleEnabled());
        CloudSignModule::get()->setEnabled(ModuleSettings::isSignModuleEnabled());
    }

    public function getModule(string $class): ?BaseModule {
        return $this->modules[$class] ?? null;
    }

    public function getModules(): array {
        return $this->modules;
    }
}