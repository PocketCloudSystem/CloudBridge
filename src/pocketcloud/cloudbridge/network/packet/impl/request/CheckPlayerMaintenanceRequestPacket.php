<?php

namespace pocketcloud\cloudbridge\network\packet\impl\request;

use pocketcloud\cloudbridge\network\packet\content\PacketContent;
use pocketcloud\cloudbridge\network\packet\RequestPacket;

class CheckPlayerMaintenanceRequestPacket extends RequestPacket {

    public function __construct(private string $player = "") {
        parent::__construct();
    }

    protected function encodePayload(PacketContent $content): void {
        parent::encodePayload($content);
        $content->put($this->player);
    }

    protected function decodePayload(PacketContent $content): void {
        parent::decodePayload($content);
        $this->player = $content->readString();
    }

    public function getPlayer(): string {
        return $this->player;
    }
}