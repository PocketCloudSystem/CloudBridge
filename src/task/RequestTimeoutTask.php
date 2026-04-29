<?php

namespace pocketcloud\cloud\bridge\task;

use pocketcloud\cloud\bridge\network\packet\impl\request\ServerHandshakeRequestPacket;
use pocketcloud\cloud\bridge\network\request\RequestManager;
use pocketcloud\cloud\bridge\util\CloudEnvironmentConfig;
use pocketmine\scheduler\Task;

final class RequestTimeoutTask extends Task {

    public function onRun(): void {
        foreach (RequestManager::getInstance()->getAll() as $request) {
            $timeout = $request instanceof ServerHandshakeRequestPacket ? CloudEnvironmentConfig::getServerTimeout() : 10;
            if (($request->getSentTimestamp() + $timeout) <= microtime(true)) {
                RequestManager::getInstance()->reject($request);
                RequestManager::getInstance()->remove($request);
            }
        }
    }
}