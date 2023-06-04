<?php

namespace pocketcloud\cloudbridge\network\request;

use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\network\Network;
use pocketcloud\cloudbridge\network\packet\RequestPacket;
use pocketcloud\cloudbridge\network\packet\ResponsePacket;
use pocketcloud\cloudbridge\task\RequestCheckTask;
use pocketmine\utils\SingletonTrait;

class RequestManager {
    use SingletonTrait;

    /** @var array<string, RequestPacket> */
    private array $requests = [];

    public function sendRequest(RequestPacket $packet): RequestPacket {
        $this->requests[$packet->getRequestId()] = $packet;
        CloudBridge::getInstance()->getScheduler()->scheduleRepeatingTask(new RequestCheckTask($packet), 20);
        Network::getInstance()->sendPacket($packet);
        return $packet;
    }

    public function removeRequest(RequestPacket|string $request) {
        $requestId = $request instanceof RequestPacket ? $request->getRequestId() : $request;
        unset($this->requests[$requestId]);
    }

    public function callThen(ResponsePacket $packet) {
        if (isset($this->requests[$packet->getRequestId()])) {
            $requestPacket = $this->requests[$packet->getRequestId()];
            if ($requestPacket instanceof RequestPacket) {
                foreach ($requestPacket->getThenClosures() as $closure) {
                    ($closure)($packet);
                }
            }
        }
    }

    public function callFailure(RequestPacket $packet) {
        if (isset($this->requests[$packet->getRequestId()])) {
            if ($packet->getFailureClosure() !== null) {
                ($packet->getFailureClosure())($packet);
            }
        }
    }

    public function getRequest(string $requestId): ?RequestPacket {
        return $this->requests[$requestId] ?? null;
    }

    public function getRequests(): array {
        return $this->requests;
    }
}