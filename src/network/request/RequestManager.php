<?php

namespace pocketcloud\cloud\bridge\network\request;

use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\network\packet\RequestPacket;
use pocketcloud\cloud\bridge\network\packet\RequestPacketFailureReason;
use pocketcloud\cloud\bridge\network\packet\ResponsePacket;
use pocketmine\utils\SingletonTrait;

final class RequestManager {
    use SingletonTrait;

    /** @var array<string, RequestPacket> */
    private array $requests = [];

    /**
     * @internal
     * @see RequestPacket
     */
    public function send(RequestPacket $packet): ?RequestPacket {
        $packet->prepare();
        if (!Network::getInstance()->sendPacket($packet)) return null;
        $this->requests[$packet->getRequestId()] = $packet;
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
                $requestPacket->invokeClosures(false, $packet);
            }
        }
    }

    public function reject(RequestPacket $packet): void {
        if (isset($this->requests[$packet->getRequestId()])) {
            $packet->invokeClosures(true, null, RequestPacketFailureReason::REQUEST_TIMEOUT);
        }
    }

    public function get(string $requestId): ?RequestPacket {
        return $this->requests[$requestId] ?? null;
    }

    public function getAll(): array {
        return $this->requests;
    }
}