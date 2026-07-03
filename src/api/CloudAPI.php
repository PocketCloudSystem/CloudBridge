<?php

namespace pocketcloud\cloud\bridge\api;

use pocketcloud\cloud\bridge\api\provider\CloudAPIProvider;
use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\api\provider\ServerGroupProvider;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\network\packet\type\LogType;
use pocketcloud\cloud\bridge\network\packet\type\VerificationStatus;
use pocketcloud\cloud\bridge\network\packet\impl\ConsoleLogPacket;
use pocketcloud\cloud\bridge\network\packet\impl\KeepAlivePacket;
use pocketcloud\cloud\bridge\network\packet\impl\request\ServerHandshakeRequestPacket;
use pocketcloud\cloud\bridge\network\packet\impl\response\ServerHandshakeResponsePacket;
use pocketcloud\cloud\bridge\network\packet\RequestPacket;
use pocketcloud\cloud\bridge\network\packet\RequestPacketFailureReason;
use pocketcloud\cloud\bridge\util\CloudEnvironmentConfig;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use Throwable;

/**
 * @template T of CloudAPIProvider
 */
final class CloudAPI {
    use SingletonTrait {
        getInstance as private;
        getInstance as public get;
    }

    private VerificationStatus $verifyStatus = VerificationStatus::PENDING;

    /**
     * @var array<class-string<T>, T>
     */
    private array $providers = [];

    public function __construct() {
        self::setInstance($this);
        $this->registerProvider(new TemplateProvider());
        $this->registerProvider(new CloudServerProvider());
        $this->registerProvider(new ServerGroupProvider());
        $this->registerProvider(new CloudPlayerProvider());
    }

    public function registerProvider(CloudAPIProvider $provider): void {
        $this->providers[$provider::class] = $provider;
    }

    public function requestLogin(): void {
        ServerHandshakeRequestPacket::create(CloudEnvironmentConfig::getServerName(), getmypid(), Server::getInstance()->getMaxPlayers())->sendRequest()->then(function (ServerHandshakeResponsePacket $packet): void {
            $status = $packet->getVerifyStatus();
            $this->verifyStatus = $status;
            if ($status === VerificationStatus::VERIFIED) {
                CloudBridge::getInstance()->setLastAliveCheck(time());
                CloudBridge::getInstance()->registerCommands();
                CloudBridge::getInstance()->startTasks();
                CloudBridge::getInstance()->getLogger()->info(LanguageKey::INGAME_SERVER_VERIFIED());
                try {
                    KeepAlivePacket::create()->sendPacket();
                } catch (Throwable $e) {
                    CloudBridge::getInstance()->getLogger()->warning("§cFailed to send first KeepAlivePacket, shutting down...");
                    CloudBridge::getInstance()->getLogger()->logException($e);
                    Server::getInstance()->shutdown();
                }
            } else {
                CloudBridge::getInstance()->getLogger()->emergency("Cloud responded with verification status '" . $status->getName() . "', shutting down this instance...");
                Server::getInstance()->shutdown();
            }
        })->failure(function (RequestPacket $packet, ?Throwable $exception, ?RequestPacketFailureReason $failureReason): void {
            if ($exception !== null) {
                CloudBridge::getInstance()->getLogger()->error("An error occurred while handling the ServerHandshakeResponsePacket (" . ($failureReason?->name ?? "Unknown failure reason") . "), shutting down this instance...");
                CloudBridge::getInstance()->getLogger()->logException($exception);
            } else {
                CloudBridge::getInstance()->getLogger()->emergency("Cloud did not respond on ServerHandshakeRequestPacket, shutting down this instance...");
            }

            Server::getInstance()->shutdown();
        });
    }

    public function logConsole(string $message, ?LogType $logType = null): void {
        ConsoleLogPacket::create($message, $logType ?? LogType::INFO)->sendPacket();
    }

    public function getVerifyStatus(): VerificationStatus {
        return $this->verifyStatus;
    }

    /**
     * @template TProvider of CloudAPIProvider
     * @param class-string<TProvider> $providerClass
     * @return TProvider|null
     */
    public function getProvider(string $providerClass): ?CloudAPIProvider {
        return $this->providers[$providerClass] ?? null;
    }
}