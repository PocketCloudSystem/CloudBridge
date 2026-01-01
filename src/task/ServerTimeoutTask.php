<?php

namespace pocketcloud\cloud\bridge\task;

use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\util\CloudEnvironmentConfig;
use pocketmine\scheduler\Task;
use pocketmine\Server;

final class ServerTimeoutTask extends Task {

    public function onRun(): void {
        if ((CloudBridge::getInstance()->getLastAliveCheck() + CloudEnvironmentConfig::getServerTimeout()) <= time()) {
            CloudBridge::getInstance()->getLogger()->warning("§cServer timed out, shutting this instance down...");
            Server::getInstance()->shutdown();
        }
    }
}