<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;
use pocketmine\Server;

final class LibrarySyncPacket extends CloudPacket implements ClientboundPacket {

    public function __construct(private array $data = []) {}

    public function handle(): void {
        foreach ($this->data as $libData) {
            [$name, $path, $namespacePrefix, $namespaceFolder] = array_values($libData);
            Server::getInstance()->getLoader()->addPath($namespacePrefix, trim($path . $namespaceFolder, DIRECTORY_SEPARATOR));
            CloudBridge::getInstance()->getLogger()->info("Loading library " . $name . " with prefix " . ($namespacePrefix == "" ? "NONE" : $namespacePrefix) . " - " . $namespaceFolder . " (" . $path . ")");
        }
    }

    public function encodePayload(PacketData $packetData): void {}

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAllTypeSafe([&$this->data], [fn() => $packetData->readArray()]);
    }

    public function getData(): array {
        return $this->data;
    }

    public static function create(array $data): self {
        return new self($data);
    }
}