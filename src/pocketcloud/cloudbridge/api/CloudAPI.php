<?php

namespace pocketcloud\cloudbridge\api;

use GlobalLogger;
use pocketcloud\cloudbridge\api\object\server\status\ServerStatus;
use pocketcloud\cloudbridge\api\provider\PlayerProvider;
use pocketcloud\cloudbridge\api\provider\ServerProvider;
use pocketcloud\cloudbridge\api\provider\TemplateProvider;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\network\Network;
use pocketcloud\cloudbridge\network\packet\impl\normal\CloudServerStatusChangePacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\ConsoleTextPacket;
use pocketcloud\cloudbridge\network\packet\impl\request\LoginRequestPacket;
use pocketcloud\cloudbridge\network\packet\impl\response\LoginResponsePacket;
use pocketcloud\cloudbridge\network\packet\impl\types\LogType;
use pocketcloud\cloudbridge\network\packet\impl\types\VerifyStatus;
use pocketcloud\cloudbridge\network\packet\ResponsePacket;
use pocketcloud\cloudbridge\network\request\RequestManager;
use pocketcloud\cloudbridge\task\ChangeStatusTask;
use pocketcloud\cloudbridge\util\GeneralSettings;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

class CloudAPI {
    use SingletonTrait;

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
        RequestManager::getInstance()->sendRequest(new LoginRequestPacket(GeneralSettings::getServerName(), getmypid(), Server::getInstance()->getMaxPlayers()))->then(function(ResponsePacket $packet): void {
            if ($packet instanceof LoginResponsePacket) {
                if ($packet->getStatus() === VerifyStatus::VERIFIED()) {
                    CloudBridge::getInstance()->getScheduler()->scheduleRepeatingTask(new ChangeStatusTask(), 20);
                    GlobalLogger::get()->info(Language::current()->translate("inGame.server.verified"));
                    $this->verified = VerifyStatus::VERIFIED();
                } else {
                    $this->verified = VerifyStatus::DENIED();
                    GlobalLogger::get()->info(Language::current()->translate("inGame.server.verify.denied"));
                    Server::getInstance()->shutdown();
                }
            }
        })->failure(function(): void {
            $this->verified = VerifyStatus::DENIED();
            GlobalLogger::get()->info(Language::current()->translate("inGame.server.verify.failed"));
            Server::getInstance()->shutdown();
        });
    }

    public function changeStatus(ServerStatus $status): void {
        Network::getInstance()->sendPacket(new CloudServerStatusChangePacket($status));
    }

    public function logConsole(string $text, ?LogType $logType = null): void {
        $logType = $logType ?? LogType::INFO();
        Network::getInstance()->sendPacket(new ConsoleTextPacket($text, $logType));
    }

    public function isVerified(): bool {
        return $this->verified === VerifyStatus::VERIFIED();
    }

    public static function playerProvider(): PlayerProvider {
        return self::$playerProvider;
    }

    public static function serverProvider(): ServerProvider {
        return self::$serverProvider;
    }

    public static function templateProvider(): TemplateProvider {
        return self::$templateProvider;
    }
}