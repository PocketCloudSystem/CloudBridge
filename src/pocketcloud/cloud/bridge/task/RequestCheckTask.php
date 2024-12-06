<?php

namespace pocketcloud\cloud\bridge\task;

use pocketcloud\cloud\bridge\network\packet\RequestPacket;
use pocketcloud\cloud\bridge\network\request\RequestManager;
use pocketmine\scheduler\Task;

class RequestCheckTask extends Task {

    public function __construct(private readonly RequestPacket $requestPacket) {}

    public function onRun(): void {
        if (isset(RequestManager::getInstance()->getRequests()[$this->requestPacket->getRequestId()])) {
            if (($this->requestPacket->getSentTime() + 10) < time()) {
                RequestManager::getInstance()->callFailure($this->requestPacket);
                RequestManager::getInstance()->removeRequest($this->requestPacket);
                $this->getHandler()->cancel();
            }
        } else {
            $this->getHandler()->cancel();
        }
    }

    public function getRequestPacket(): RequestPacket {
        return $this->requestPacket;
    }
}