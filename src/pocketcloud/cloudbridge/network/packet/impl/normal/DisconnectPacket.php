<?php

namespace pocketcloud\cloudbridge\network\packet\impl\normal;

use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\content\PacketContent;
use pocketcloud\cloudbridge\network\packet\impl\types\DisconnectReason;

class DisconnectPacket extends CloudPacket {

    public function __construct(private ?DisconnectReason $disconnectReason = null) {}

    protected function encodePayload(PacketContent $content): void {
        parent::encodePayload($content);
        $content->putDisconnectReason($this->disconnectReason);
    }

    protected function decodePayload(PacketContent $content): void {
        parent::decodePayload($content);
        $this->disconnectReason = $content->readDisconnectReason();
    }

    public function getDisconnectReason(): DisconnectReason {
        return $this->disconnectReason;
    }
}