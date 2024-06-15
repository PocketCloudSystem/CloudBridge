<?php

namespace pocketcloud\cloudbridge\network\packet\impl\normal;

use pocketcloud\cloudbridge\api\object\player\CloudPlayer;
use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;

class PlayerConnectPacket extends CloudPacket {

    public function __construct(private ?CloudPlayer $player = null) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writePlayer($this->player);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->player = $packetData->readPlayer();
    }

    public function getPlayer(): ?CloudPlayer{
        return $this->player;
    }

    public function handle(): void {}
}