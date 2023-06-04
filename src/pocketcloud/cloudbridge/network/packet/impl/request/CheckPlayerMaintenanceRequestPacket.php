<?php

namespace pocketcloud\cloudbridge\network\packet\impl\request;

use pocketcloud\cloudbridge\network\packet\RequestPacket;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;

class CheckPlayerMaintenanceRequestPacket extends RequestPacket {

    public function __construct(private string $player = "") {
        parent::__construct();
    }

    public function encodePayload(PacketData $packetData) {
        $packetData->write($this->player);
    }

    public function decodePayload(PacketData $packetData) {
        $this->player = $packetData->readString();
    }

    public function getPlayer(): string {
        return $this->player;
    }
}