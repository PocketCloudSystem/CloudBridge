<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use Exception;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;
use r3pt1s\forms\Forms;
use Symfony\Component\Filesystem\Path;

final class LibrarySyncPacket extends CloudPacket implements ClientboundPacket {

    public function __construct(private array $data = []) {}

    public static function create(array $data): self {
        return new self($data);
    }

    public function handle(): void {
        foreach ($this->data as $libData) {
            [$name, $path, $namespacePrefix, $namespaceFolder] = array_values($libData);
            CloudBridge::getInstance()->getLibraryClassLoader()->addPrefix($namespacePrefix, Path::join($path, $namespaceFolder));
            CloudBridge::getInstance()->getLogger()->info("Loading library " .
                $name .
                " with prefix " .
                ($namespacePrefix == "" ? "NONE" : $namespacePrefix) .
                " - " .
                $namespaceFolder .
                " (" .
                Path::join($path, $namespaceFolder) .
                ")");
        }

        if (!Forms::isRegistered()) try {
            Forms::register(CloudBridge::getInstance());
        } catch (Exception $e) {
            CloudBridge::getInstance()->getLogger()->logException($e);
        }
    }

    public function encodePayload(PacketData $packetData): void {}

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAllTypeSafe([&$this->data], [fn() => $packetData->readArray()]);
    }

    public function getData(): array {
        return $this->data;
    }
}