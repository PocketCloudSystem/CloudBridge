<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\command\sender\CloudCommandSender;
use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\data\ServerCommandExecutionResult;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;
use pocketmine\Server;

final class CommandExecutePacket extends CloudPacket implements ClientboundPacket {

    public function __construct(
        private string $commandLine = "",
        private string $id = ""
    ) {}

    public function handle(): void {
        $commandSender = new CloudCommandSender($this->id, Server::getInstance(), Server::getInstance()->getLanguage());
        Server::getInstance()->dispatchCommand($commandSender, $this->commandLine);
        CommandAnswerPacket::create(new ServerCommandExecutionResult($this->id, $this->commandLine, $commandSender->getCachedMessages()))->sendPacket();
    }

    public function encodePayload(PacketData $packetData): void {}

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAll($this->commandLine, $this->id);
    }

    public function getCommandLine(): string {
        return $this->commandLine;
    }

    public function getId(): string {
        return $this->id;
    }

    public static function create(string $commandLine, string $id): self {
        return new self($commandLine, $id);
    }
}