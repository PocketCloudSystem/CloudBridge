<?php

namespace pocketcloud\cloudbridge\network\packet;

use pocketcloud\cloudbridge\network\packet\content\PacketContent;

class ResponsePacket extends CloudPacket {

    public function __construct(private string $requestId) {}

    public function encode(PacketContent $content): void {
        parent::encode($content);
        $content->put($this->requestId);
    }

    public function decode(PacketContent $content): void {
        parent::decode($content);
        $this->requestId = $content->readString();
    }

    public function getRequestId(): string {
        return $this->requestId;
    }
}