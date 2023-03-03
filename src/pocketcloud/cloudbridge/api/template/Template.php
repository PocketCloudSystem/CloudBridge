<?php

namespace pocketcloud\cloudbridge\api\template;

use pocketcloud\cloudbridge\utils\Utils;

class Template {

    public function __construct(private string $name, private bool $lobby, private bool $maintenance, private bool $static, private int $maxPlayerCount, private int $minServerCount, private int $maxServerCount, private bool $startNewWhenFull, private bool $autoStart, private string $templateType) {}

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

    public function getMaxPlayerCount(): int {
        return $this->maxPlayerCount;
    }

    public function getMinServerCount(): int {
        return $this->minServerCount;
    }

    public function getMaxServerCount(): int {
        return $this->maxServerCount;
    }

    public function isStartNewWhenFull(): bool {
        return $this->startNewWhenFull;
    }

    public function isAutoStart(): bool {
        return $this->autoStart;
    }

    public function getTemplateType(): string {
        return $this->templateType;
    }

    public function setLobby(bool $value): void {
        $this->lobby = $value;
    }

    public function setMaintenance(bool $value): void {
        $this->maintenance = $value;
    }

    public function setMaxPlayerCount(int $maxPlayerCount): void {
        $this->maxPlayerCount = $maxPlayerCount;
    }

    public function setMinServerCount(int $minServerCount): void {
        $this->minServerCount = $minServerCount;
    }

    public function setMaxServerCount(int $maxServerCount): void {
        $this->maxServerCount = $maxServerCount;
    }

    public function setAutoStart(bool $autoStart): void {
        $this->autoStart = $autoStart;
    }

    public function apply(array $data) {
        $this->name = $data["name"];
        $this->lobby = $data["lobby"];
        $this->maintenance = $data["maintenance"];
        $this->static = $data["static"];
        $this->maxPlayerCount = $data["maxPlayerCount"];
        $this->minServerCount = $data["minServerCount"];
        $this->maxServerCount = $data["maxServerCount"];
        $this->startNewWhenFull = $data["startNewWhenFull"];
        $this->autoStart = $data["autoStart"];
        $this->templateType = $data["templateType"];
    }

    public function toArray(): array {
        return [
            "name" => $this->name,
            "lobby" => $this->lobby,
            "maintenance" => $this->maintenance,
            "static" => $this->static,
            "maxPlayerCount" => $this->maxPlayerCount,
            "minServerCount" => $this->minServerCount,
            "maxServerCount" => $this->maxServerCount,
            "startNewWhenFull" => $this->startNewWhenFull,
            "autoStart" => $this->autoStart,
            "templateType" => $this->templateType
        ];
    }

    public static function fromArray(array $template): ?Template {
        if (!Utils::containKeys($template, "name", "lobby", "maintenance", "static", "maxPlayerCount", "minServerCount", "maxServerCount", "startNewWhenFull", "autoStart", "templateType")) return null;
        return new Template(
            $template["name"],
            boolval($template["lobby"]),
            boolval($template["maintenance"]),
            boolval($template["static"]),
            intval($template["maxPlayerCount"]),
            intval($template["minServerCount"]),
            intval($template["maxServerCount"]),
            boolval($template["startNewWhenFull"]),
            boolval($template["autoStart"]),
            $template["templateType"]
        );
    }
}