<?php

namespace pocketcloud\cloudbridge\network\packet\impl\normal;

use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\impl\types\DisconnectReason;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;
use pocketmine\Server;

class DisconnectPacket extends CloudPacket {

    public function __construct(private ?DisconnectReason $disconnectReason = null) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeDisconnectReason($this->disconnectReason);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->disconnectReason = $packetData->readDisconnectReason();
    }

    public function handle(): void {
        if ($this->disconnectReason === DisconnectReason::CLOUD_SHUTDOWN()) {
            \GlobalLogger::get()->emergency("§4Cloud was stopped! Shutdown...");
        } else {
            \GlobalLogger::get()->emergency("§4Server shutdown was ordered by the cloud! Shutdown...");
        }

        Server::getInstance()->shutdown();
    }

    public function getDisconnectReason(): ?DisconnectReason {
        return $this->disconnectReason;
    }
}