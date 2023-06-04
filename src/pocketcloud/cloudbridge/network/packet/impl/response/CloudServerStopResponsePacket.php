<?php

namespace pocketcloud\cloudbridge\network\packet\impl\response;

use pocketcloud\cloudbridge\network\packet\impl\types\ErrorReason;
use pocketcloud\cloudbridge\network\packet\ResponsePacket;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;

class CloudServerStopResponsePacket extends ResponsePacket {

    public function __construct(
        string $requestId = "",
        private ?ErrorReason $errorReason = null
    ) {
        parent::__construct($requestId);
    }

    public function encodePayload(PacketData $packetData) {
        $packetData->writeErrorReason($this->errorReason);
    }

    public function decodePayload(PacketData $packetData) {
        $this->errorReason = $packetData->readErrorReason();
    }

    public function getErrorReason(): ?ErrorReason {
        return $this->errorReason;
    }

    public function handle() {}
}