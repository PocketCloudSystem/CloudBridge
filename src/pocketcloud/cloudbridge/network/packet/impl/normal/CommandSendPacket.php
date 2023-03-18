<?php

namespace pocketcloud\cloudbridge\network\packet\impl\normal;

use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\content\PacketContent;

class CommandSendPacket extends CloudPacket {

    public function __construct(private string $commandLine = "") {}

    protected function encodePayload(PacketContent $content): void {
        parent::encodePayload($content);
        $content->put($this->commandLine);
    }

    protected function decodePayload(PacketContent $content): void {
        parent::decodePayload($content);
        $this->commandLine = $content->readString();
    }

    public function getCommandLine(): string {
        return $this->commandLine;
    }
}