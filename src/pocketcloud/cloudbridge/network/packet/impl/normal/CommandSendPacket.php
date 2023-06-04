<?php

namespace pocketcloud\cloudbridge\network\packet\impl\normal;

use pocketcloud\cloudbridge\network\Network;
use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\impl\types\CommandExecutionResult;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;
use pocketcloud\cloudbridge\util\CloudCommandSender;
use pocketmine\Server;

class CommandSendPacket extends CloudPacket {

    public function __construct(private string $commandLine = "") {}

    public function encodePayload(PacketData $packetData) {
        $packetData->write($this->commandLine);
    }

    public function decodePayload(PacketData $packetData) {
        $this->commandLine = $packetData->readString();
    }

    public function getCommandLine(): string {
        return $this->commandLine;
    }

    public function handle() {
        Server::getInstance()->dispatchCommand($sender = new CloudCommandSender(), $this->commandLine);
        Network::getInstance()->sendPacket(new CommandSendAnswerPacket(
            new CommandExecutionResult($this->commandLine, $sender->getCachedMessages())
        ));
    }
}