<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\data\ServerDisconnectReason;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;
use pocketmine\Server;

final class DisconnectPacket extends CloudPacket implements ClientboundPacket, CloudboundPacket {

    public function __construct(private ?ServerDisconnectReason $reason = null) {}

    public static function create(ServerDisconnectReason $reason): self {
        return new self($reason);
    }

    public function handle(): void {
        if ($this->reason === ServerDisconnectReason::CLOUD_SHUTDOWN) {
            CloudBridge::getInstance()->getLogger()->warning("§4Cloud was stopped, shutdown down this instance...");
        } else {
            CloudBridge::getInstance()->getLogger()->warning("§4Server shutdown was ordered by the cloud, shutdown down this instance...");
        }

        Server::getInstance()->shutdown();
    }

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeAll($this->reason);
    }

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAllTypeSafe([&$this->reason], [fn() => $packetData->readServerDisconnectReason()]);
    }

    public function getReason(): ?ServerDisconnectReason {
        return $this->reason;
    }
}