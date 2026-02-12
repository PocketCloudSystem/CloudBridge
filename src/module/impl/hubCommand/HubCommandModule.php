<?php

namespace pocketcloud\cloud\bridge\module\impl\hubCommand;

use pocketcloud\cloud\bridge\api\cache\InGameModuleCache;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\module\Module;

final class HubCommandModule extends Module {

    public function __construct() {
        parent::__construct(InGameModuleCache::HUB_COMMAND_MODULE, "Activate the /hub command for the sub-servers");
    }

    public function onEnable(): void {
        CloudBridge::getInstance()->getServer()->getCommandMap()->register("cloudBridge", new HubCommand());
    }

    public function onDisable(): void {
        if (($cmd = CloudBridge::getInstance()->getServer()->getCommandMap()->getCommand("hub")) !== null) {
            CloudBridge::getInstance()->getServer()->getCommandMap()->unregister($cmd);
        }
    }
}