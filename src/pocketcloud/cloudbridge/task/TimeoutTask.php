<?php

namespace pocketcloud\cloudbridge\task;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\CloudBridge;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class TimeoutTask extends Task {

    public function onRun(): void {
        if (!CloudAPI::getInstance()->isVerified()) return;
        if ((CloudBridge::getInstance()->lastKeepALiveCheck + 20) <= microtime(true)) {
            \GlobalLogger::get()->warning("§cServer timeout! Shutdown...");
            Server::getInstance()->shutdown();
        }
    }
}