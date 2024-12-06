<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\normal;

use pocketcloud\cloud\bridge\command\sender\CloudCommandSender;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\impl\type\CommandExecutionResult;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;
use pocketmine\Server;

class CommandSendPacket extends CloudPacket {

    public function __construct(private string $commandLine = "") {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->commandLine);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->commandLine = $packetData->readString();
    }

    public function getCommandLine(): string {
        return $this->commandLine;
    }

    public function handle(): void {
        Server::getInstance()->dispatchCommand($sender = new CloudCommandSender(), $this->commandLine);
        CommandSendAnswerPacket::create(new CommandExecutionResult(
            $this->commandLine, $sender->getCachedMessages()
        ))->sendPacket();
    }
}