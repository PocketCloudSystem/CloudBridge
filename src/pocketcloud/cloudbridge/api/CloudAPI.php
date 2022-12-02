<?php

namespace pocketcloud\cloudbridge\api;

use pocketcloud\cloudbridge\api\player\CloudPlayer;
use pocketcloud\cloudbridge\api\registry\Registry;
use pocketcloud\cloudbridge\api\server\CloudServer;
use pocketcloud\cloudbridge\api\server\status\ServerStatus;
use pocketcloud\cloudbridge\api\template\Template;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\config\ModulesConfig;
use pocketcloud\cloudbridge\module\npc\CloudNPCManager;
use pocketcloud\cloudbridge\module\sign\CloudSignManager;
use pocketcloud\cloudbridge\network\Network;
use pocketcloud\cloudbridge\network\packet\impl\normal\CloudServerSavePacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\CloudServerStatusChangePacket;
use pocketcloud\cloudbridge\network\packet\impl\request\CloudServerStartRequestPacket;
use pocketcloud\cloudbridge\network\packet\impl\request\CloudServerStopRequestPacket;
use pocketcloud\cloudbridge\network\packet\impl\request\LoginRequestPacket;
use pocketcloud\cloudbridge\network\packet\impl\response\LoginResponsePacket;
use pocketcloud\cloudbridge\network\packet\impl\types\VerifyStatus;
use pocketcloud\cloudbridge\network\packet\RequestPacket;
use pocketcloud\cloudbridge\network\packet\ResponsePacket;
use pocketcloud\cloudbridge\network\request\RequestManager;
use pocketcloud\cloudbridge\task\ChangeStatusTask;
use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Internet;
use pocketmine\utils\SingletonTrait;
use pocketmine\Server;

class CloudAPI {
    use SingletonTrait;

    private VerifyStatus $verified;

    public function __construct() {
        self::setInstance($this);
        $this->verified = VerifyStatus::NOT_VERIFIED();
    }

    public function processLogin() {
        if ($this->verified === VerifyStatus::VERIFIED()) return;
        RequestManager::getInstance()->sendRequest(new LoginRequestPacket($this->getServerName(), getmypid()))->then(function(ResponsePacket $packet): void {
            if ($packet instanceof LoginResponsePacket) {
                if ($packet->getVerifyStatus() === VerifyStatus::VERIFIED()) {
                    CloudBridge::getInstance()->getScheduler()->scheduleRepeatingTask(new ChangeStatusTask(), 20);
                    \GlobalLogger::get()->info("This cloud server was §averified §fby the cloud!");
                    CloudBridge::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function(): void {
                        if (ModulesConfig::getInstance()->isSignModule()) CloudSignManager::getInstance()->load();
                        if (ModulesConfig::getInstance()->isNpcModule()) CloudNPCManager::getInstance()->load();
                    }), 60);
                    $this->verified = VerifyStatus::VERIFIED();
                } else {
                    \GlobalLogger::get()->error("§4This cloud server wasn't verified §4by the cloud! Shutdown...");
                    Server::getInstance()->shutdown();
                }
            }
        })->failure(function(): void {
            \GlobalLogger::get()->error("§4The cloud didn't respond on the verify request! Shutdown...");
            Server::getInstance()->shutdown();
        });
    }

    public function changeStatus(ServerStatus $status) {
        Network::getInstance()->sendPacket(new CloudServerStatusChangePacket($status));
    }

    public function startServer(Template|string $template, int $count = 1): RequestPacket {
        $template = $template instanceof Template ? $template->getName() : $template;
        return RequestManager::getInstance()->sendRequest(new CloudServerStartRequestPacket($template, $count));
    }

    public function stopServer(CloudServer|string $server): RequestPacket {
        $server = $server instanceof CloudServer ? $server->getName() : $server;
        return RequestManager::getInstance()->sendRequest(new CloudServerStopRequestPacket($server));
    }

    public function stopTemplate(Template|string $template): RequestPacket {
        $template = $template instanceof Template ? $template->getName() : $template;
        return RequestManager::getInstance()->sendRequest(new CloudServerStopRequestPacket($template));
    }

    public function saveCurrentServer() {
        Network::getInstance()->sendPacket(new CloudServerSavePacket());
    }

    public function transferPlayer(Player $player, CloudServer $server, ?CloudPlayer $cloudPlayer = null): bool {
        $cloudPlayer = ($cloudPlayer === null ? $this->getPlayerByName($player->getName()) : $cloudPlayer);
        if ($cloudPlayer !== null) {
            if ($server->getServerStatus() === ServerStatus::IN_GAME() || $server->getServerStatus() === ServerStatus::FULL() || $server->getServerStatus() === ServerStatus::STOPPING()) return false;
            if ($server->getTemplate()->isMaintenance() && !$player->hasPermission("pocketcloud.maintenance.bypass")) return false;
            if ($cloudPlayer->getCurrentProxy() === null) return $player->transfer(Internet::getInternalIP(), $server->getCloudServerData()->getPort());
            else return $player->getNetworkSession()->sendDataPacket(TransferPacket::create($server->getName(), $server->getCloudServerData()->getPort()));
        }
        return false;
    }

    /** @return array<CloudServer> */
    public function getServersOfTemplate(Template $template): array {
        return array_filter($this->getServers(), function(CloudServer $server) use($template): bool {
            return $template->getName() == $server->getTemplate()->getName();
        });
    }

    /** @return array<CloudPlayer> */
    public function getPlayersOfTemplate(Template $template): array {
        return array_filter($this->getPlayers(), function(CloudPlayer $player) use($template): bool {
            if ($template->getTemplateType() == "PROXY") return ($player->getCurrentProxy() !== null && $player->getCurrentProxy()->getTemplate()->getName() == $template->getName());
            else return ($player->getCurrentServer() !== null && $player->getCurrentServer()->getTemplate()->getName() == $template->getName());
        });
    }

    public function getServerByName(string $name): ?CloudServer {
        return Registry::getServers()[$name] ?? null;
    }

    public function getTemplateByName(string $name): ?Template {
        return Registry::getTemplates()[$name] ?? null;
    }

    public function getPlayerByName(string $name): ?CloudPlayer {
        return Registry::getPlayers()[$name] ?? null;
    }

    public function getPlayerByUniqueId(string $uniqueId): ?CloudPlayer {
        return array_filter(Registry::getPlayers(), fn(CloudPlayer $player) => $player->getUniqueId() == $uniqueId)[0] ?? null;
    }

    public function getPlayerByXboxUserId(string $xboxUserId): ?CloudPlayer {
        return array_filter(Registry::getPlayers(), fn(CloudPlayer $player) => $player->getXboxUserId() == $xboxUserId)[0] ?? null;
    }

    public function getServerName(): string {
        return Server::getInstance()->getConfigGroup()->getConfigString("server-name");
    }

    public function getCloudPort(): int {
        return Server::getInstance()->getConfigGroup()->getConfigInt("cloud-port");
    }

    public function getCloudPath(): string {
        return Server::getInstance()->getConfigGroup()->getConfigString("cloud-path");
    }

    public function getTemplateName(): string {
        return Server::getInstance()->getConfigGroup()->getConfigString("template");
    }

    public function getCurrentServer(): ?CloudServer {
        return $this->getServerByName($this->getServerName());
    }

    public function getCurrentTemplate(): ?Template {
        return $this->getTemplateByName($this->getTemplateName());
    }

    /** @return array<CloudServer> */
    public function getServers(): array {
        return Registry::getServers();
    }

    /** @return array<Template> */
    public function getTemplates(): array {
        return Registry::getTemplates();
    }

    /** @return array<CloudPlayer> */
    public function getPlayers(): array {
        return Registry::getPlayers();
    }

    public function isVerified(): bool {
        return $this->verified === VerifyStatus::VERIFIED();
    }
}