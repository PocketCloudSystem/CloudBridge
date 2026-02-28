<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\response\client;

use pocketcloud\cloud\bridge\network\packet\data\ServerCommandExecutionResult;
use pocketcloud\cloud\bridge\network\packet\ResponseClientPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;

final class CommandExecuteResponsePacket extends ResponseClientPacket {

    public function __construct(private readonly ?ServerCommandExecutionResult $commandExecutionResult = null) {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeAll($this->commandExecutionResult);
    }

    public function decodePayload(PacketData $packetData): void {}

    public function getCommandExecutionResult(): ?ServerCommandExecutionResult {
        return $this->commandExecutionResult;
    }

    public static function create(ServerCommandExecutionResult $commandExecutionResult): self {
        return new self($commandExecutionResult);
    }
}