<?php

namespace pocketcloud\cloud\bridge\task;

use pocketcloud\cloud\bridge\api\CloudAPI;
use pocketcloud\cloud\bridge\api\object\server\status\ServerStatus;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class ChangeStatusTask extends Task {

    public function onRun(): void {
        if (CloudAPI::servers()->current()->getServerStatus() === ServerStatus::IN_GAME() || CloudAPI::servers()->current()?->getServerStatus() === ServerStatus::STOPPING()) return;
        if (count(Server::getInstance()->getOnlinePlayers()) >= (CloudAPI::templates()->current()->getMaxPlayerCount() ?? Server::getInstance()->getMaxPlayers())) {
            CloudAPI::get()->changeStatus(ServerStatus::FULL());
        } else {
            if (CloudAPI::servers()->current()->getServerStatus() === ServerStatus::FULL()) {
                CloudAPI::get()->changeStatus(ServerStatus::ONLINE());
            }
        }
    }
}