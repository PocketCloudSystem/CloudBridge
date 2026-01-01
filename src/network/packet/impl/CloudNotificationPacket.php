<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class CloudNotificationPacket extends CloudPacket implements ClientboundPacket {

    public function __construct(private string $message = "") {}

    public function handle(): void {

    }

    public function encodePayload(PacketData $packetData): void {}

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAll($this->message);
    }

    public function getMessage(): string {
        return $this->message;
    }

    public static function create(string $message): self {
        return new self($message);
    }
}