<?php

namespace pocketcloud\cloud\bridge\task;

use pocketcloud\cloud\bridge\api\object\server\util\ServerStatus;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketmine\scheduler\Task;
use pocketmine\Server;

final class StatusChangeTask extends Task {

    public function onRun(): void {
        if (
            CloudServerProvider::provider()->current()->getServerStatus() === ServerStatus::IN_GAME ||
            CloudServerProvider::provider()->current()?->getServerStatus() === ServerStatus::STOPPING
        ) return;

        if (count(Server::getInstance()->getOnlinePlayers()) >=
            (TemplateProvider::provider()->current()->getMaxPlayerCount() ?? Server::getInstance()->getMaxPlayers())
        ) {
            CloudServerProvider::provider()->current()->setServerStatus(ServerStatus::FULL);
        } else {
            if (CloudServerProvider::provider()->current()->getServerStatus() === ServerStatus::FULL) {
                CloudServerProvider::provider()->current()->setServerStatus(ServerStatus::ONLINE);
            }
        }
    }
}