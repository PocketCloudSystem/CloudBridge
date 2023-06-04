<?php

namespace pocketcloud\cloudbridge\task;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\language\Language;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class TimeoutTask extends Task {

    public function onRun(): void {
        if (!CloudAPI::getInstance()->isVerified()) return;
        if ((CloudBridge::getInstance()->lastKeepALiveCheck + 10) <= time()) {
            \GlobalLogger::get()->warning(Language::current()->translate("inGame.server.timeout"));
            Server::getInstance()->shutdown();
        }
    }
}