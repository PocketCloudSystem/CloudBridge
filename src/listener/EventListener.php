<?php

namespace pocketcloud\cloud\bridge\listener;

use pocketcloud\cloud\bridge\api\cache\MaintenanceListCache;
use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\command\BaseCloudCommand;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\network\packet\data\NotificationType;
use pocketcloud\cloud\bridge\network\packet\impl\PlayerConnectPacket;
use pocketcloud\cloud\bridge\network\packet\impl\PlayerDisconnectPacket;
use pocketcloud\cloud\bridge\player\PlayerSessionManager;
use pocketcloud\cloud\bridge\util\CloudEnvironmentConfig;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\lang\Translatable;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\player\Player;
use pocketmine\Server;

final class EventListener implements Listener {

    private bool $intercepting = false;

    /**
     * @priority HIGHEST
     * @param DataPacketSendEvent $event
     * @return void
     */
    public function onDataPacketSend(DataPacketSendEvent $event): void {
        foreach ($event->getTargets() as $target) {
            $player = $target->getPlayer();
            foreach ($event->getPackets() as $packet) {
                if ($packet instanceof AvailableCommandsPacket) {
                    if (!$player instanceof Player) continue;
                    if ($this->intercepting) continue;

                    $this->intercepting = true;
                    $event->cancel();
                    $target->sendDataPacket(BaseCloudCommand::createCommandsPacket($player));
                    $this->intercepting = false;
                }
            }
        }
    }

    /**
     * @priority MONITOR
     * @param PlayerPreLoginEvent $event
     * @return void
     */
    public function onPreLoginEvent(PlayerPreLoginEvent $event): void {
        if (!$event->isAllowed()) {
            $finalReason = ($event->getFinalDisconnectReason()
            instanceof
            Translatable ? Server::getInstance()->getLanguage()->translate($event->getFinalDisconnectReason()) : $event->getFinalDisconnectReason());
            NotificationType::PLAYER_JOIN_FAILED->notify([
                "player" => $event->getPlayerInfo()->getUsername(),
                "server" => CloudEnvironmentConfig::getServerName(),
                "reason" => $finalReason
            ]);
        }
    }

    /**
     * @priority MONITOR
     * @handleCancelled
     * @param PlayerLoginEvent $event
     * @return void
     */
    public function onLogin(PlayerLoginEvent $event): void {
        if (TemplateProvider::provider()->current()->isMaintenance() &&
            !MaintenanceListCache::is($event->getPlayer()->getName()) &&
            !$event->getPlayer()->hasPermission("pocketcloud.bypass.maintenance")) {
            $event->setKickMessage(LanguageKey::INGAME_TEMPLATE_KICK_MAINTENANCE()->translate());
            $event->cancel();
            NotificationType::PLAYER_JOIN_FAILED->notify([
                "player" => $event->getPlayer()->getName(),
                "server" => CloudEnvironmentConfig::getServerName(),
                "reason" => "Template is in maintenance"
            ]);
            return;
        }

        if ($event->isCancelled()) {
            $finalReason = ($event->getKickMessage()
            instanceof
            Translatable ? Server::getInstance()->getLanguage()->translate($event->getKickMessage()) : $event->getKickMessage());
            NotificationType::PLAYER_JOIN_FAILED->notify([
                "player" => $event->getPlayer()->getName(),
                "server" => CloudEnvironmentConfig::getServerName(),
                "reason" => $finalReason
            ]);
            return;
        }

        PlayerConnectPacket::create(CloudPlayer::fromPlayer($event->getPlayer()))->sendPacket();
    }

    /**
     * @priority HIGHEST
     * @param PlayerJoinEvent $event
     * @return void
     */
    public function onJoin(PlayerJoinEvent $event): void {
        PlayerSessionManager::getInstance()->create($event->getPlayer());
    }

    /**
     * @priority MONITOR
     * @param PlayerQuitEvent $event
     * @return void
     */
    public function onQuit(PlayerQuitEvent $event): void {
        PlayerDisconnectPacket::create($event->getPlayer()->getName())->sendPacket();
    }

    /**
     * @priority MONITOR
     * @handleCancelled
     * @param PlayerKickEvent $event
     * @return void
     */
    public function onKick(PlayerKickEvent $event): void {
        if ($event->isCancelled()) return;
        $finalReason = ($event->getDisconnectReason()
        instanceof
        Translatable ? Server::getInstance()->getLanguage()->translate($event->getDisconnectReason()) : $event->getDisconnectReason());
        if ($event->getPlayer()->spawned) {
            NotificationType::PLAYER_KICKED->notify([
                "player" => $event->getPlayer()->getName(),
                "server" => CloudEnvironmentConfig::getServerName(),
                "reason" => $finalReason
            ]);
        } else {
            NotificationType::PLAYER_JOIN_FAILED->notify([
                "player" => $event->getPlayer()->getName(),
                "server" => CloudEnvironmentConfig::getServerName(),
                "reason" => $finalReason
            ]);
        }
    }
}