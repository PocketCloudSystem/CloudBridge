<?php

namespace pocketcloud\cloud\bridge\task;

use pocketcloud\cloud\bridge\network\request\RequestManager;
use pocketmine\scheduler\Task;

final class RequestTimeoutTask extends Task {

    public function onRun(): void {
        foreach (RequestManager::getInstance()->getAll() as $request) {
            if (($request->getSentTimestamp() + 10) <= microtime(true)) {
                RequestManager::getInstance()->reject($request);
                RequestManager::getInstance()->remove($request);
            }
        }
    }
}