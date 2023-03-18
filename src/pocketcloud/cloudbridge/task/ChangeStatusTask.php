<?php

namespace pocketcloud\cloudbridge\task;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\server\status\ServerStatus;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class ChangeStatusTask extends Task {

    public function onRun(): void {
        if (CloudAPI::getInstance()->getCurrentServer()?->getServerStatus() === ServerStatus::IN_GAME() || CloudAPI::getInstance()->getCurrentServer()?->getServerStatus() === ServerStatus::STOPPING()) return;
        if (count(Server::getInstance()->getOnlinePlayers()) >= (CloudAPI::getInstance()->getCurrentTemplate()?->getMaxPlayerCount() ?? Server::getInstance()->getMaxPlayers())) {
            CloudAPI::getInstance()->changeStatus(ServerStatus::FULL());
        } else {
            if (CloudAPI::getInstance()->getCurrentServer()?->getServerStatus() === ServerStatus::FULL()) {
                CloudAPI::getInstance()->changeStatus(ServerStatus::ONLINE());
            }
        }
    }
}