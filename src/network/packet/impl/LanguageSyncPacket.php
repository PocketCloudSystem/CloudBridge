<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\language\Language;
use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class LanguageSyncPacket extends CloudPacket implements ClientboundPacket {

    public function __construct(
        private string $language = "",
        private array $messages = []
    ) {}

    public function handle(): void {
        Language::sync($this->language, $this->messages);
    }

    public function encodePayload(PacketData $packetData): void {}

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAllTypeSafe([&$this->language, &$this->messages], [
            fn() => $packetData->readString(),
            fn() => json_decode(gzdecode(base64_decode($packetData->readString())), true, 512, JSON_THROW_ON_ERROR),
        ]);
    }

    public static function create(string $language, array $messages): self {
        return new self($language, $messages);
    }
}