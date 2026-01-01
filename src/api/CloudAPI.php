<?php

namespace pocketcloud\cloud\bridge\api;

use pocketcloud\cloud\bridge\api\provider\CloudAPIProvider;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\network\packet\data\VerifyStatus;
use pocketcloud\cloud\bridge\network\packet\impl\KeepAlivePacket;
use pocketcloud\cloud\bridge\network\packet\impl\request\ServerHandshakeRequestPacket;
use pocketcloud\cloud\bridge\network\packet\impl\response\ServerHandshakeResponsePacket;
use pocketcloud\cloud\bridge\util\CloudEnvironmentConfig;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

final class CloudAPI {
    use SingletonTrait {
        getInstance as private;
        getInstance as public get;
    }

    private VerifyStatus $verifyStatus = VerifyStatus::NOT_APPLIED;

    private array $providers = [];

    public function __construct() {
        self::setInstance($this);
    }

    public function requestLogin(): void {
        ServerHandshakeRequestPacket::makeRequest(CloudEnvironmentConfig::getServerName(), getmypid(), Server::getInstance()->getMaxPlayers())->then(function (ServerHandshakeResponsePacket $packet): void {
            $status = $packet->getVerifyStatus();
            $this->verifyStatus = $status;
            if ($status === VerifyStatus::VERIFIED) {
#                CloudBridge::getInstance()->getLogger()->info(Language::current()->translate("inGame.server.verified"));
                CloudBridge::getInstance()->getLogger()->info("inGame.server.verified");
                KeepAlivePacket::create()->sendPacket(); # Start keep-alive cycle
            } else {
                CloudBridge::getInstance()->getLogger()->emergency("Cloud responded with verification status '" . $status->getName() . "', shutting down this instance...");
                Server::getInstance()->shutdown();
            }
        })->failure(function (): void {
            CloudBridge::getInstance()->getLogger()->emergency("Cloud did not respond on ServerHandshakeRequestPacket, shutting down this instance...");
            Server::getInstance()->shutdown();
        });
    }

    public function registerProvider(CloudAPIProvider $provider): void {
        $this->providers[$provider::class] = $provider;
    }

    public function getProvider(string $providerClass): ?CloudAPIProvider {
        return $this->providers[$providerClass] ?? null;
    }
}