<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\api\object\group\ServerGroup;
use pocketcloud\cloud\bridge\api\provider\ServerGroupProvider;
use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class ServerGroupSyncPacket extends CloudPacket implements ClientboundPacket {

    public function __construct(
        private ?ServerGroup $group = null,
        private bool $removal = false
    ) {}

    public static function create(ServerGroup $group, bool $removal): self {
        return new self($group, $removal);
    }

    public function handle(): void {
        if ($this->removal) ServerGroupProvider::provider()->remove($this->group);
        else ServerGroupProvider::provider()->add($this->group);
    }

    public function encodePayload(PacketData $packetData): void {}

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAllTypeSafe([&$this->group, &$this->removal], [fn() => $packetData->readServerGroup(), fn() => $packetData->readBool()]);
    }

    public function getGroup(): ?ServerGroup {
        return $this->group;
    }

    public function isRemoval(): bool {
        return $this->removal;
    }
}