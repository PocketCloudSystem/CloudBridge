<?php

namespace pocketcloud\cloudbridge\network\packet\impl\request;

use pocketcloud\cloudbridge\network\packet\content\PacketContent;
use pocketcloud\cloudbridge\network\packet\RequestPacket;

class CloudServerStopRequestPacket extends RequestPacket {

    public function __construct(private string $server = "") {
        parent::__construct();
    }

    protected function encodePayload(PacketContent $content): void {
        parent::encodePayload($content);
        $content->put($this->server);
    }

    protected function decodePayload(PacketContent $content): void {
        parent::decodePayload($content);
        $this->server = $content->readString();
    }

    public function getServer(): string {
        return $this->server;
    }
}