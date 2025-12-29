<?php

namespace pocketcloud\cloud\bridge\network\packet;

use pocketcloud\cloud\bridge\network\packet\util\PacketData;

interface Packet {

    public function encode(PacketData $packetData): void;

    public function encodePayload(PacketData $packetData): void;

    public function decode(PacketData $packetData): void;

    public function decodePayload(PacketData $packetData): void;

    public function handle(): void;

    public function getName(): string;

    public function isEncoded(): bool;

    public function getSentTimestamp(): ?int;
}