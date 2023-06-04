<?php

namespace pocketcloud\cloudbridge\network\packet\impl\response;

use pocketcloud\cloudbridge\network\packet\impl\types\VerifyStatus;
use pocketcloud\cloudbridge\network\packet\ResponsePacket;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;

class LoginResponsePacket extends ResponsePacket {

    public function __construct(
        string $requestId = "",
        private ?VerifyStatus $status = null
    ) {
        parent::__construct($requestId);
    }

    public function encodePayload(PacketData $packetData) {
        $packetData->writeVerifyStatus($this->status);
    }

    public function decodePayload(PacketData $packetData) {
        $this->status = $packetData->readVerifyStatus();
    }

    public function getStatus(): ?VerifyStatus {
        return $this->status;
    }

    public function handle() {}
}