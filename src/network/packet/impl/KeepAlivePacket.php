<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class KeepAlivePacket extends CloudPacket implements ClientboundPacket, CloudboundPacket {

    public function handle(): void {
        CloudBridge::getInstance()->setLastAliveCheck(time());
        KeepAlivePacket::create()->sendPacket();
    }

    public function encodePayload(PacketData $packetData): void {}

    public function decodePayload(PacketData $packetData): void {}

    public static function create(): self {
        return new self();
    }
}