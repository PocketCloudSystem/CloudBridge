<?php

namespace pocketcloud\cloud\bridge\network\packet;

use ErrorException;
use JsonException;
use pmmp\thread\ThreadSafe;
use pocketcloud\cloud\bridge\exception\PacketException;
use pocketcloud\cloud\bridge\network\packet\util\PacketSerializer;
use pocketcloud\cloud\bridge\util\net\Address;

final class UnhandledPacket extends ThreadSafe {

    public function __construct(
        private readonly string $buffer,
        private readonly Address $address,
        private readonly int $bytes
    ) {}

    /**
     * @throws PacketException|ErrorException|JsonException
     */
    public function buildCloudPacket(bool $encryptionEnabled, string $authenticationKey): ?ClientboundPacket {
        return PacketSerializer::decode($this->buffer, $encryptionEnabled, $authenticationKey);
    }

    public function getBuffer(): string {
        return $this->buffer;
    }

    public function getAddress(): Address {
        return $this->address;
    }

    public function getBytes(): int {
        return $this->bytes;
    }
}