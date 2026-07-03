<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\request\client;

use pocketcloud\cloud\bridge\command\sender\CloudCommandSender;
use pocketcloud\cloud\bridge\network\packet\type\ServerCommandExecutionResult;
use pocketcloud\cloud\bridge\network\packet\impl\response\client\CommandExecuteResponsePacket;
use pocketcloud\cloud\bridge\network\packet\RequestClientPacket;
use pocketcloud\cloud\bridge\network\packet\util\PacketData;
use pocketmine\Server;

final class CommandExecuteRequestPacket extends RequestClientPacket {

    public function __construct(
        private string $commandLine = "",
        private string $id = ""
    ) {}

    public function handle(): void {
        $commandSender = new CloudCommandSender($this->id, Server::getInstance(), Server::getInstance()->getLanguage());
        Server::getInstance()->dispatchCommand($commandSender, $this->commandLine);
        $this->sendResponse(CommandExecuteResponsePacket::create(new ServerCommandExecutionResult($this->id, $this->commandLine, $commandSender->getCachedMessages())));
    }

    public static function create(string $commandLine, string $id): self {
        return new self($commandLine, $id);
    }

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAll($this->commandLine, $this->id);
    }

    public function getCommandLine(): string {
        return $this->commandLine;
    }

    public function getId(): string {
        return $this->id;
    }
}