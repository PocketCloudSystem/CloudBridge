<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;
use pocketcloud\cloud\bridge\util\ProcessUtils;
use pocketmine\Server;

final class KeepAlivePacket extends CloudPacket implements ClientboundPacket, CloudboundPacket {

    public function __construct(
        private float $tps = -1,
        private float $avgTps = -1,
        private float $memoryUsage = -1,
        private float $memoryPeak = -1,
        private float $memoryLimit = -1,
        private float $cpuUsage = -1
    ) {}

    public function handle(): void {
        CloudBridge::getInstance()->setLastAliveCheck(time());
        KeepAlivePacket::create()->sendPacket();
    }

    public static function create(): self {
        [$memoryUsage, $peakMemoryUsage] = array_values(ProcessUtils::getProcessStatus());
        return new self(
            Server::getInstance()->getTicksPerSecond(),
            Server::getInstance()->getTicksPerSecondAverage(),
            $memoryUsage,
            $peakMemoryUsage,
            ProcessUtils::getMemoryLimit(),
            ProcessUtils::getCpuUsage()
        );
    }

    public function encodePayload(PacketData $packetData): void {
        $packetData->writeAll($this->tps, $this->avgTps, $this->memoryUsage, $this->memoryPeak, $this->memoryLimit, $this->cpuUsage);
    }

    public function decodePayload(PacketData $packetData): void {
        $packetData->readAll($this->tps, $this->avgTps, $this->memoryUsage, $this->memoryPeak, $this->memoryLimit, $this->cpuUsage);
    }
}