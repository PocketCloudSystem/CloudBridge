<?php

namespace pocketcloud\cloudbridge\network\packet\impl\request;

use pocketcloud\cloudbridge\network\packet\utils\PacketData;
use pocketcloud\cloudbridge\network\packet\RequestPacket;

class CheckPlayerNotifyRequestPacket extends RequestPacket {

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