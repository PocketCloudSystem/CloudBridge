<?php

namespace pocketcloud\cloudbridge\network\packet\impl\normal;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\object\player\CloudPlayer;
use pocketcloud\cloudbridge\api\registry\Registry;
use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;

class PlayerSyncPacket extends CloudPacket {

    public function __construct(
        private ?CloudPlayer $player = null,
        private bool $removal = false
    ) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writePlayer($this->player);
        $packetData->write($this->removal);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->player = $packetData->readPlayer();
        $this->removal = $packetData->readBool();
    }

    public function getPlayer(): ?CloudPlayer {
        return $this->player;
    }

    public function isRemoval(): bool {
        return $this->removal;
    }

    public function handle(): void {
        if (CloudAPI::playerProvider()->getPlayer($this->player->getName()) === null) {
            if (!$this->removal) Registry::registerPlayer($this->player);
        } else {
            if ($this->removal) {
                Registry::unregisterPlayer($this->player->getName());
            } else if ($this->player->getCurrentServer() !== null) {
                Registry::updatePlayer($this->player->getName(), $this->player->getCurrentServer()->getName());
            }
        }
    }
}