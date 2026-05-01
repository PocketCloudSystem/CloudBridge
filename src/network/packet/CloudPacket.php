<?php

namespace pocketcloud\cloud\bridge\network\packet;

use pocketcloud\cloud\bridge\exception\NetworkException;
use pocketcloud\cloud\bridge\exception\PacketException;
use pocketcloud\cloud\bridge\exception\PacketTooLargeException;
use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;
use ReflectionClass;
use RuntimeException;

/**
 * ClientboundPacket -> Server (Client) is the receiver, Cloud is the sender
 * CloudboundPacket -> Cloud is the receiver, Server (Client) is the sender
 */
abstract class CloudPacket implements Packet {

    private bool $encoded = false;
    private ?float $sentTimestamp = null;

    public function encode(PacketData $packetData): void {
        if ($this->encoded) throw new RuntimeException("Packet " . $this->getName() . " has already been encoded");
        $this->encoded = true;
        $packetData->writeAll($this->getName(), $this->sentTimestamp = microtime(true));
        $this->encodePayload($packetData);
    }

    final public function getName(): string {
        return new ReflectionClass($this)->getShortName();
    }

    public function decode(PacketData $packetData): void {
        $packetName = $packetData->readString();
        if ($packetName !==
            $this->getName()) throw new RuntimeException("Packet name does not equal the actual class name? What have you done?");
        $this->sentTimestamp = $packetData->readFloat();
        if ($this->sentTimestamp ===
            null) throw new RuntimeException("Packet data does not contain the actual sent timestamp? What have you done?");
        $this->decodePayload($packetData);
    }

    /**
     * @return void
     * @throws NetworkException|PacketException|PacketTooLargeException
     */
    public function sendPacket(): void {
        if (!$this instanceof CloudboundPacket) return;
        Network::getInstance()->sendPacket($this);
    }

    abstract public function handle(): void;

    public function isEncoded(): bool {
        return $this->encoded;
    }

    public function getSentTimestamp(): ?float {
        return $this->sentTimestamp;
    }
}