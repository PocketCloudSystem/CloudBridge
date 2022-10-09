<?php

namespace pocketcloud\cloudbridge\api\template;

use pocketcloud\cloudbridge\utils\Utils;

class Template {

    public function __construct(private string $name, private bool $lobby, private bool $maintenance, private int $maxPlayerCount, private int $minServerCount, private int $maxServerCount, private bool $autoStart, private string $templateType) {}

    public function getName(): string {
        return $this->name;
    }

    public function isLobby(): bool {
        return $this->lobby;
    }

    public function isMaintenance(): bool {
        return $this->maintenance;
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
        $this->maxPlayerCount = $data["maxPlayerCount"];
        $this->minServerCount = $data["minServerCount"];
        $this->maxServerCount = $data["maxServerCount"];
        $this->autoStart = $data["autoStart"];
        $this->templateType = $data["templateType"];
    }

    public function toArray(): array {
        return [
            "name" => $this->name,
            "lobby" => $this->lobby,
            "maintenance" => $this->maintenance,
            "maxPlayerCount" => $this->maxPlayerCount,
            "minServerCount" => $this->minServerCount,
            "maxServerCount" => $this->maxServerCount,
            "autoStart" => $this->autoStart,
            "templateType" => $this->templateType
        ];
    }

    public static function fromArray(array $template): ?Template {
        if (!Utils::containKeys($template, "name", "lobby", "maintenance", "maxPlayerCount", "minServerCount", "maxServerCount", "autoStart", "templateType")) return null;
        return new Template(
            $template["name"],
            boolval($template["lobby"]),
            boolval($template["maintenance"]),
            intval($template["maxPlayerCount"]),
            intval($template["minServerCount"]),
            intval($template["maxServerCount"]),
            boolval($template["autoStart"]),
            $template["templateType"]
        );
    }
}