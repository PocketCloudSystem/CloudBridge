<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\type\LogType;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;

final class ConsoleLogPacket extends CloudPacket implements ClientboundPacket, CloudboundPacket {

    public function __construct(
        private string $message = "",
        private ?LogType $logType = null
    ) {}

    public function handle(): void {
        CloudBridge::getInstance()->getLogger()->log($this->logType->toLogLevel(), $this->message);
    }

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeAll($this->message, $this->logType);
    }

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAllTypeSafe([&$this->message, &$this->logType], [fn() => $packetData->readString(), fn() => $packetData->readEnum(LogType::class)]);
    }

    public function getMessage(): string {
        return $this->message;
    }

    public function getLogType(): ?LogType {
        return $this->logType;
    }

    public static function create(string $message, LogType $logType): self {
        return new self($message, $logType);
    }
}