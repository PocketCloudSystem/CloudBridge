<?php

namespace pocketcloud\cloudbridge\network\packet\impl\normal;

use pocketcloud\cloudbridge\network\packet\CloudPacket;
use pocketcloud\cloudbridge\network\packet\impl\types\LogType;
use pocketcloud\cloudbridge\network\packet\utils\PacketData;

class ConsoleTextPacket extends CloudPacket {

    public function __construct(
        private string $text = "",
        private ?LogType $logType = null
    ) {
        if ($this->logType === null) $this->logType = LogType::INFO();
    }

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->text);
        $packetData->writeLogType($this->logType);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->text = $packetData->readString();
        $this->logType = $packetData->readLogType();
    }

    public function getText(): string {
        return $this->text;
    }

    public function getLogType(): ?LogType {
        return $this->logType;
    }

    public function handle(): void {
        if ($this->logType === LogType::INFO()) \GlobalLogger::get()->info($this->text);
        else if ($this->logType === LogType::DEBUG()) \GlobalLogger::get()->debug($this->text, true);
        else if ($this->logType === LogType::WARN()) \GlobalLogger::get()->warning($this->text);
        else if ($this->logType === LogType::ERROR()) \GlobalLogger::get()->error($this->text);
    }
}