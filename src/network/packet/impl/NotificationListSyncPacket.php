<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\api\cache\MaintenanceListCache;
use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class NotificationListSyncPacket extends CloudPacket implements ClientboundPacket {

    public function __construct(private array $list = []) {}

    public function handle(): void {
        MaintenanceListCache::sync($this->list);
    }

    public function encodePayload(PacketData $packetData): void {}

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAll($this->list);
    }

    public function getList(): array {
        return $this->list;
    }

    public static function create(array $list): self {
        return new self($list);
    }
}