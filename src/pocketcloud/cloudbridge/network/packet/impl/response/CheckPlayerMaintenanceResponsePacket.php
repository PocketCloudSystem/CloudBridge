<?php

namespace pocketcloud\cloudbridge\network\packet\impl\response;

use pocketcloud\cloudbridge\network\packet\ResponsePacket;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;

class CheckPlayerMaintenanceResponsePacket extends ResponsePacket {

    public function __construct(
        string $requestId = "",
        private bool $value = false
    ) {
        parent::__construct($requestId);
    }

    public function encodePayload(PacketData $packetData) {
        $packetData->write($this->value);
    }

    public function decodePayload(PacketData $packetData) {
        $this->value = $packetData->readBool();
    }

    public function getValue(): bool {
        return $this->value;
    }

    public function handle() {}
}