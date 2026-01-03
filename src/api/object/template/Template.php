<?php

namespace pocketcloud\cloud\bridge\api\object\template;

use pocketcloud\cloud\bridge\api\object\group\ServerGroup;
use pocketcloud\cloud\bridge\api\provider\ServerGroupProvider;
use pocketcloud\cloud\bridge\util\misc\Writeable;
use pocketcloud\cloud\bridge\util\Utils;

final class Template implements Writeable {

    public function __construct(
        private readonly string $name,
        private bool $lobby,
        private bool $maintenance,
        private bool $static,
        private bool $alwaysCopyToStaticServers,
        private int $maxPlayerCount,
        private int $minServerCount,
        private int $maxServerCount,
        private float $startNewPercentage,
        private bool $autoStart,
        private readonly string $templateType
    ) {}

    /** @internal */
    public function sync(array $data): void {
        $this->lobby = $data["lobby"] ?? $this->lobby;
        $this->maintenance = $data["maintenance"] ?? $this->maintenance;
        $this->static = $data["static"] ?? $this->static;
        $this->alwaysCopyToStaticServers = $data["alwaysCopyToStaticServers"] ?? $this->alwaysCopyToStaticServers;
        $this->maxPlayerCount = $data["maxPlayerCount"] ?? $this->maxPlayerCount;
        $this->minServerCount = $data["minServerCount"] ?? $this->minServerCount;
        $this->maxServerCount = $data["maxServerCount"] ?? $this->maxServerCount;
        $this->startNewPercentage = $data["startNewPercentage"] ?? $this->startNewPercentage;
        $this->autoStart = $data["autoStart"] ?? $this->autoStart;
    }

    public function getName(): string {
        return $this->name;
    }

    public function isLobby(): bool {
        return $this->lobby;
    }

    public function isMaintenance(): bool {
        return $this->maintenance;
    }

    public function isStatic(): bool {
        return $this->static;
    }

    public function isAlwaysCopyToStaticServers(): bool {
        return $this->alwaysCopyToStaticServers;
    }

    public function getMaxPlayerCount(): int {
        return $this->maxPlayerCount;
    }

    public function getMinServerCount(): int {
        return $this->minServerCount;
    }

    public function getMaxServerCount(): int {
        return $this->maxServerCount;
    }

    public function getStartNewPercentage(): float {
        return $this->startNewPercentage;
    }

    public function isAutoStart(): bool {
        return $this->autoStart;
    }

    public function getTemplateType(): string {
        return $this->templateType;
    }

    public function getParentServerGroup(): ?ServerGroup {
        return ServerGroupProvider::provider()->get($this);
    }

    public function write(): array {
        return [
            "name" => $this->name,
            "lobby" => $this->lobby,
            "maintenance" => $this->maintenance,
            "static" => $this->static,
            "alwaysCopyToStaticServers" => $this->alwaysCopyToStaticServers,
            "maxPlayerCount" => $this->maxPlayerCount,
            "minServerCount" => $this->minServerCount,
            "maxServerCount" => $this->maxServerCount,
            "startNewPercentage" => $this->startNewPercentage,
            "autoStart" => $this->autoStart,
            "templateType" => $this->templateType
        ];
    }

    public static function read(array $data): ?Template {
        if (!Utils::containKeys($data, "name", "lobby", "maintenance", "static", "alwaysCopyToStaticServers", "maxPlayerCount", "minServerCount", "maxServerCount", "startNewPercentage", "autoStart", "templateType")) return null;
        return new Template(
            $data["name"],
            boolval($data["lobby"]),
            boolval($data["maintenance"]),
            boolval($data["static"]),
            boolval($data["alwaysCopyToStaticServers"]),
            intval($data["maxPlayerCount"]),
            intval($data["minServerCount"]),
            intval($data["maxServerCount"]),
            boolval($data["startNewPercentage"]),
            boolval($data["autoStart"]),
            $data["templateType"]
        );
    }
}