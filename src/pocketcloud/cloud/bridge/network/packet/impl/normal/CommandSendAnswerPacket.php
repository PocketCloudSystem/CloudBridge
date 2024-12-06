<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\normal;

use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\impl\type\CommandExecutionResult;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;

class CommandSendAnswerPacket extends CloudPacket {

    public function __construct(private ?CommandExecutionResult $result = null) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeCommandExecutionResult($this->result);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->result = $packetData->readCommandExecutionResult();
    }

    public function getResult(): ?CommandExecutionResult {
        return $this->result;
    }

    public function handle(): void {}
}