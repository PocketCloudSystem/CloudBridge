<?php

namespace pocketcloud\cloud\bridge\network\request;

use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\network\packet\RequestPacket;
use pocketcloud\cloud\bridge\network\packet\ResponsePacket;
use pocketcloud\cloud\bridge\task\RequestCheckTask;
use pocketmine\utils\SingletonTrait;

final class RequestManager {
    use SingletonTrait;

    /** @var array<string, RequestPacket> */
    private array $requests = [];

    public function send(RequestPacket $packet): RequestPacket {
        $packet->prepare();
        $this->requests[$packet->getRequestId()] = $packet;
        CloudBridge::getInstance()->getScheduler()->scheduleRepeatingTask(new RequestCheckTask($packet), 20);
        Network::getInstance()->sendPacket($packet);
        return $packet;
    }

    public function remove(RequestPacket|string $request): void {
        $requestId = $request instanceof RequestPacket ? $request->getRequestId() : $request;
        unset($this->requests[$requestId]);
    }

    public function resolve(ResponsePacket $packet): void {
        if (isset($this->requests[$packet->getRequestId()])) {
            $requestPacket = $this->requests[$packet->getRequestId()];
            if ($requestPacket instanceof RequestPacket) {
                foreach ($requestPacket->getThenClosures() as $closure) {
                    ($closure)($packet);
                }
            }
        }
    }

    public function reject(RequestPacket $packet): void {
        if (isset($this->requests[$packet->getRequestId()])) {
            if ($packet->getFailureClosure() !== null) {
                ($packet->getFailureClosure())($packet);
            }
        }
    }

    public function get(string $requestId): ?RequestPacket {
        return $this->requests[$requestId] ?? null;
    }

    public function getAll(): array {
        return $this->requests;
    }
}