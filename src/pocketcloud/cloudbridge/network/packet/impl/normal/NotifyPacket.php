<?php

namespace pocketcloud\cloudbridge\network\packet\impl\normal;

use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\content\PacketContent;

class NotifyPacket extends CloudPacket {

    public function __construct(private string $message = "", private array $players = []) {}

    protected function encodePayload(PacketContent $content): void {
        parent::encodePayload($content);
        $content->put($this->message);
        $content->put($this->players);
    }

    protected function decodePayload(PacketContent $content): void {
        parent::decodePayload($content);
        $this->message = $content->readString();
        $this->players = $content->readArray();
    }

    public function getMessage(): string {
        return $this->message;
    }

    public function getPlayers(): array {
        return $this->players;
    }
}