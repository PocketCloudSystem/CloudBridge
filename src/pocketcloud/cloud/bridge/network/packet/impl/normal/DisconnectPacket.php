<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\normal;

use GlobalLogger;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;
use pocketcloud\cloud\bridge\network\packet\impl\type\DisconnectReason;
use pocketmine\Server;

final class DisconnectPacket extends CloudPacket {

    public function __construct(private ?DisconnectReason $disconnectReason = null) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeDisconnectReason($this->disconnectReason);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->disconnectReason = $packetData->readDisconnectReason();
    }

    public function handle(): void {
        if ($this->disconnectReason === DisconnectReason::CLOUD_SHUTDOWN()) {
            GlobalLogger::get()->emergency("§4Cloud was stopped! Shutdown...");
        } else {
            GlobalLogger::get()->emergency("§4Server shutdown was ordered by the cloud! Shutdown...");
        }

        Server::getInstance()->shutdown();
    }

    public function getDisconnectReason(): ?DisconnectReason {
        return $this->disconnectReason;
    }
}