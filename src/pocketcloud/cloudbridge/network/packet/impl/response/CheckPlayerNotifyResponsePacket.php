<?php

namespace pocketcloud\cloudbridge\network\packet\impl\response;

use pocketcloud\cloudbridge\network\packet\content\PacketContent;
use pocketcloud\cloudbridge\network\packet\ResponsePacket;

class CheckPlayerNotifyResponsePacket extends ResponsePacket {

    public function __construct(private string $requestId = "", private string $player = "", private bool $value = false) {
        parent::__construct($this->requestId);
    }

    protected function encodePayload(PacketContent $content): void {
        parent::encodePayload($content);
        $content->put($this->player);
        $content->put($this->value);
    }

    protected function decodePayload(PacketContent $content): void {
        parent::decodePayload($content);
        $this->player = $content->readString();
        $this->value = $content->readBool();
    }

    public function getPlayer(): string {
        return $this->player;
    }

    public function getValue(): bool {
        return $this->value;
    }
}