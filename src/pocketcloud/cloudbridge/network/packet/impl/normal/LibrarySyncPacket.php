<?php

namespace pocketcloud\cloudbridge\network\packet\impl\normal;

use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;
use pocketmine\Server;

class LibrarySyncPacket extends CloudPacket {

    private array $data = [];

    public function __construct() {}

    public function encodePayload(PacketData $packetData) {
        $packetData->write($this->data);
    }

    public function decodePayload(PacketData $packetData) {
        $this->data = $packetData->readArray();
    }

    public function getData(): array {
        return $this->data;
    }

    public function handle() {
        foreach ($this->data as $lib) {
            Server::getInstance()->getLoader()->addPath("", $lib["path"]);
        }
    }
}