<?php

namespace pocketcloud\cloudbridge\network\packet\impl\response;

use pocketcloud\cloudbridge\network\packet\content\PacketContent;
use pocketcloud\cloudbridge\network\packet\impl\types\VerifyStatus;
use pocketcloud\cloudbridge\network\packet\ResponsePacket;

class LoginResponsePacket extends ResponsePacket {

    public function __construct(private string $requestId = "", private ?VerifyStatus $verifyStatus = null) {
        parent::__construct($this->requestId);
    }

    protected function encodePayload(PacketContent $content): void {
        parent::encodePayload($content);
        $content->putVerifyStatus($this->verifyStatus);
    }

    protected function decodePayload(PacketContent $content): void {
        parent::decodePayload($content);
        $this->verifyStatus = $content->readVerifyStatus();
    }

    public function getVerifyStatus(): VerifyStatus {
        return $this->verifyStatus;
    }
}