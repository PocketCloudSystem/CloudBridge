<?php

namespace pocketcloud\cloud\bridge\api\object\player;

use pocketcloud\cloud\bridge\util\Utils;

final class CloudPlayer {

    public function __construct(
        private readonly string $name,
        private readonly string $address,
        private readonly string $xboxUserId,
        private readonly string $uniqueId,
        private ?string $currentServer,
        private ?string $currentProxy
    ) {}

    public function setCurrentServer(?string $currentServer): void {
        $this->currentServer = $currentServer;
    }

    public function setCurrentProxy(?string $currentProxy): void {
        $this->currentProxy = $currentProxy;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getAddress(): string {
        return $this->address;
    }

    public function getXboxUserId(): string {
        return $this->xboxUserId;
    }

    public function getUniqueId(): string {
        return $this->uniqueId;
    }

    public function getCurrentServer(): ?string {
        return $this->currentServer;
    }

    public function getCurrentProxy(): ?string {
        return $this->currentProxy;
    }

    public function getCurrentServerName(): ?string {
        return $this->currentServer;
    }

    public function getCurrentProxyName(): ?string {
        return $this->currentProxy;
    }

    public function write(): array {
        return [
            "name" => $this->name,
            "address" => $this->address,
            "xboxUserId" => $this->xboxUserId,
            "uniqueId" => $this->uniqueId,
            "currentServer" => $this->currentServer,
            "currentProxy" => $this->currentProxy
        ];
    }

    public static function read(array $player): ?self {
        if (!Utils::containKeys($player, "name", "address", "xboxUserId", "uniqueId")) return null;
        return new CloudPlayer(
            $player["name"],
            $player["address"],
            $player["xboxUserId"],
            $player["uniqueId"],
            (!isset($player["currentServer"]) ? null : $player["currentServer"]),
            (!isset($player["currentProxy"]) ? null : $player["currentProxy"])
        );
    }
}