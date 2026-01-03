<?php

namespace pocketcloud\cloud\bridge\api\object\player;

use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\util\misc\Writeable;
use pocketcloud\cloud\bridge\util\Utils;
use pocketmine\player\Player;

final class CloudPlayer implements Writeable {

    public function __construct(
        private readonly string $name,
        private readonly string $address,
        private readonly string $xboxUserId,
        private readonly string $uniqueId,
        private ?string $currentServer,
        private ?string $currentProxy
    ) {}

    /** @internal */
    public function sync(array $data): void {
        $this->currentServer = array_key_exists("currentServer", $data) ? $data["currentServer"] : $this->currentServer;
        $this->currentProxy = array_key_exists("currentProxy", $data) ? $data["currentProxy"] : $this->currentProxy;
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

    public function setCurrentServer(CloudServer|string|null $currentServer): void {
        $currentServer = ($currentServer instanceof CloudServer ? $currentServer->getName() : (is_string($currentServer) ? $currentServer : null));
        $this->currentServer = $currentServer;
    }

    public function setCurrentProxy(CloudServer|string|null $currentProxy): void {
        $currentProxy = ($currentProxy instanceof CloudServer ? $currentProxy->getName() : (is_string($currentProxy) ? $currentProxy : null));
        $this->currentProxy = $currentProxy;
    }

    public function getCurrentServer(): ?CloudServer {
        return CloudServerProvider::provider()->get($this->currentServer);
    }

    public function getCurrentProxy(): ?CloudServer {
        return CloudServerProvider::provider()->get($this->currentProxy);
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

    public static function read(array $data): ?self {
        if (!Utils::containKeys($data, "name", "address", "xboxUserId", "uniqueId")) return null;
        return new CloudPlayer(
            $data["name"],
            $data["address"],
            $data["xboxUserId"],
            $data["uniqueId"],
            $data["currentServer"] ?? null,
            $data["currentProxy"] ?? null
        );
    }

    public static function fromPlayer(Player $player): self {
        return new self(
            $player->getName(),
            $player->getNetworkSession()->getIp(),
            $player->getXuid(),
            $player->getUniqueId()->toString(),
            null, null
        );
    }
}