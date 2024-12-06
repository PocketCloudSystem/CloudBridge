<?php

namespace pocketcloud\cloud\bridge\api;

use GlobalLogger;
use pocketcloud\cloud\bridge\api\object\server\status\ServerStatus;
use pocketcloud\cloud\bridge\api\provider\PlayerProvider;
use pocketcloud\cloud\bridge\api\provider\ServerProvider;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\network\packet\impl\normal\CloudServerStatusChangePacket;
use pocketcloud\cloud\bridge\network\packet\impl\normal\ConsoleTextPacket;
use pocketcloud\cloud\bridge\network\packet\impl\request\ServerHandshakeRequestPacket;
use pocketcloud\cloud\bridge\network\packet\impl\types\LogType;
use pocketcloud\cloud\bridge\network\packet\impl\types\VerifyStatus;
use pocketcloud\cloud\bridge\task\ChangeStatusTask;
use pocketcloud\cloud\bridge\util\GeneralSettings;
use pocketcloud\cloud\network\packet\impl\response\ServerHandshakeResponsePacket;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

class CloudAPI {
    use SingletonTrait {
        getInstance as public get;
        getInstance as private getInstance;
    }

    private VerifyStatus $verified;
    private static PlayerProvider $playerProvider;
    private static ServerProvider $serverProvider;
    private static TemplateProvider $templateProvider;

    public function __construct() {
        self::setInstance($this);
        $this->verified = VerifyStatus::NOT_APPLIED();

        self::$playerProvider = new PlayerProvider();
        self::$serverProvider = new ServerProvider();
        self::$templateProvider = new TemplateProvider();
    }

    public function processLogin(): void {
        if ($this->verified === VerifyStatus::VERIFIED()) return;
        ServerHandshakeRequestPacket::makeRequest(
            GeneralSettings::getServerName(), getmypid(), Server::getInstance()->getMaxPlayers()
        )->then(function (ServerHandshakeResponsePacket $packet): void {
            if ($packet->getVerifyStatus() === VerifyStatus::VERIFIED()) {
                CloudBridge::getInstance()->getScheduler()->scheduleRepeatingTask(new ChangeStatusTask(), 20);
                GlobalLogger::get()->info("§aSuccessfully verified by the cloud.");
                $this->verified = VerifyStatus::VERIFIED();
            } else {
                $this->verified = VerifyStatus::DENIED();
                GlobalLogger::get()->warning("§cVerification was denied, shutting down...");
                Server::getInstance()->shutdown();
            }
        })->failure(function(): void {
            $this->verified = VerifyStatus::DENIED();
            GlobalLogger::get()->warning("§cFailed to verify cloud server, shutting down...");
            Server::getInstance()->shutdown();
        });
    }

    public function changeStatus(ServerStatus $status): void {
        CloudServerStatusChangePacket::create($status)->sendPacket();
    }

    public function logConsole(string $text, ?LogType $logType = null): void {
        ConsoleTextPacket::create($text, $logType ?? LogType::INFO())->sendPacket();
    }

    public function isVerified(): bool {
        return $this->verified === VerifyStatus::VERIFIED();
    }

    public static function players(): PlayerProvider {
        return self::$playerProvider;
    }

    public static function servers(): ServerProvider {
        return self::$serverProvider;
    }

    public static function templates(): TemplateProvider {
        return self::$templateProvider;
    }
}