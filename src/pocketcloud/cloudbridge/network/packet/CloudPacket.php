<?php

namespace pocketcloud\cloudbridge\network\packet;

use pocketcloud\cloudbridge\network\packet\utils\PacketData;
use pocketcloud\cloudbridge\util\Utils;

abstract class CloudPacket {

    private bool $encoded = false;

    public function encode(PacketData $packetData) {
        if (!$this->encoded) {
            $this->encoded = true;
            $packetData->write((new \ReflectionClass($this))->getShortName());
            $this->encodePayload($packetData);
        }
    }

    public function decode(PacketData $packetData) {
        $packetData->readString();
        $this->decodePayload($packetData);
    }

    public function encodePayload(PacketData $packetData) {}

    public function decodePayload(PacketData $packetData) {}

    abstract public function handle();

    public function isEncoded(): bool {
        return $this->encoded;
    }
}