<?php

namespace pocketcloud\cloudbridge\network\packet\impl\normal;

use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\content\PacketContent;

class PlayerNotifyUpdatePacket extends CloudPacket {

    public function __construct(private string $player = "", private bool $value = false) {}

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