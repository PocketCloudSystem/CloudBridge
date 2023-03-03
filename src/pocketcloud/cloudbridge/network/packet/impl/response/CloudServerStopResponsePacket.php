<?php

namespace pocketcloud\cloudbridge\network\packet\impl\response;

use pocketcloud\cloudbridge\network\packet\content\PacketContent;
use pocketcloud\cloudbridge\network\packet\impl\types\ErrorReason;
use pocketcloud\cloudbridge\network\packet\ResponsePacket;

class CloudServerStopResponsePacket extends ResponsePacket {

    public function __construct(private string $requestId = "", private ?ErrorReason $errorReason = null) {
        parent::__construct($this->requestId);
    }

    protected function encodePayload(PacketContent $content): void {
        parent::encodePayload($content);
        $content->putErrorReason($this->errorReason);
    }

    protected function decodePayload(PacketContent $content): void {
        parent::decodePayload($content);
        $this->errorReason = $content->readErrorReason();
    }

    public function getErrorReason(): ?ErrorReason {
        return $this->errorReason;
    }
}