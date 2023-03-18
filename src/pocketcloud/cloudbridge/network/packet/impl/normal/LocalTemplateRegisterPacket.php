<?php

namespace pocketcloud\cloudbridge\network\packet\impl\normal;

use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\content\PacketContent;

class LocalTemplateRegisterPacket extends CloudPacket {

    public function __construct(private array $template = []) {}

    protected function encodePayload(PacketContent $content): void {
        parent::encodePayload($content);
        $content->put($this->template);
    }

    protected function decodePayload(PacketContent $content): void {
        parent::decodePayload($content);
        $this->template = $content->readArray();
    }

    public function getTemplate(): array {
        return $this->template;
    }
}