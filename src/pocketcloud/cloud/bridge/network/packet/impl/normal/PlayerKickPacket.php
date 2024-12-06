<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\normal;

use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;
use pocketmine\Server;

class PlayerKickPacket extends CloudPacket {

    public function __construct(
        private string $playerName = "",
        private string $reason = ""
    ) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->playerName);
        $packetData->write($this->reason);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->playerName = $packetData->readString();
        $this->reason = $packetData->readString();
    }

    public function getPlayerName(): string {
        return $this->playerName;
    }

    public function getReason(): string {
        return $this->reason;
    }

    public function handle(): void {
        if (($player = Server::getInstance()->getPlayerExact($this->playerName)) !== null) {
            if ($this->reason == "MAINTENANCE") {
                if (!$player->hasPermission("pocketcloud.maintenance.bypass")) $player->kick("§cThis server is in maintenance.");
            } else {
                $player->kick($this->reason);
            }
        }
    }
}