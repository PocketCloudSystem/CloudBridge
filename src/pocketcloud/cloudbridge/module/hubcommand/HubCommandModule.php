<?php

namespace pocketcloud\cloudbridge\module\hubcommand;

use pocketcloud\cloudbridge\module\BaseModule;
use pocketmine\Server;

final class HubCommandModule extends BaseModule {

    protected function onEnable(): void {
        $this->getServer()->getCommandMap()->register("hubCommand", new HubCommand());
        foreach (Server::getInstance()->getOnlinePlayers() as $player) $player->getNetworkSession()->syncAvailableCommands();
    }

    protected function onDisable(): void {
        if (($cmd = $this->getServer()->getCommandMap()->getCommand("hub")) !== null) $this->getServer()->getCommandMap()->unregister($cmd);
        foreach (Server::getInstance()->getOnlinePlayers() as $player) $player->getNetworkSession()->syncAvailableCommands();
    }
}