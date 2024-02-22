<?php

namespace pocketcloud\cloudbridge\module;

use pocketcloud\cloudbridge\module\globalchat\GlobalChatModule;
use pocketcloud\cloudbridge\module\hubcommand\HubCommandModule;
use pocketcloud\cloudbridge\module\npc\CloudNPCModule;
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
            CloudNPCModule::class => new CloudNPCModule()
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
        if (ModuleSettings::isHubCommandModuleEnabled()) HubCommandModule::get()->setEnabled();
        if (ModuleSettings::isGlobalChatModuleEnabled()) GlobalChatModule::get()->setEnabled();
        if (ModuleSettings::isNpcModuleEnabled()) CloudNPCModule::get()->setEnabled();
        if (ModuleSettings::isNpcModuleEnabled()) HubCommandModule::get()->setEnabled();

    }

    public function getModule(string $class): ?BaseModule {
        return $this->modules[$class] ?? null;
    }

    public function getModules(): array {
        return $this->modules;
    }
}