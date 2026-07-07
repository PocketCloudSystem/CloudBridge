<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\response\client;

use pocketcloud\cloud\bridge\network\packet\type\ServerCommandExecutionResult;
use pocketcloud\cloud\bridge\network\packet\ResponseClientPacket;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;

final class CommandExecuteResponsePacket extends ResponseClientPacket {

    public function __construct(private readonly ?ServerCommandExecutionResult $commandExecutionResult = null) {}

    public static function create(ServerCommandExecutionResult $commandExecutionResult): self {
        return new self($commandExecutionResult);
    }

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeAll($this->commandExecutionResult);
    }

    public function getCommandExecutionResult(): ?ServerCommandExecutionResult {
        return $this->commandExecutionResult;
    }
}