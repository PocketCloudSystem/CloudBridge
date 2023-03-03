<?php

namespace pocketcloud\cloudbridge\network\packet\impl\normal;

use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\content\PacketContent;
use pocketcloud\cloudbridge\api\server\status\ServerStatus;

class LocalServerUpdatePacket extends CloudPacket {

    public function __construct(private string $server = "", private ?ServerStatus $newStatus = null) {}

    protected function encodePayload(PacketContent $content): void {
        parent::encodePayload($content);
        $content->put($this->server);
        $content->putServerStatus($this->newStatus);
    }

    protected function decodePayload(PacketContent $content): void {
        parent::decodePayload($content);
        $this->server = $content->readString();
        $this->newStatus = $content->readServerStatus();
    }

    public function getServer(): string {
        return $this->server;
    }

    public function getNewStatus(): ?ServerStatus {
        return $this->newStatus;
    }
}