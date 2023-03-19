<?php

namespace pocketcloud\cloudbridge\network\packet\handler;

use pocketcloud\cloudbridge\api\player\CloudPlayer;
use pocketcloud\cloudbridge\api\registry\Registry;
use pocketcloud\cloudbridge\api\server\CloudServer;
use pocketcloud\cloudbridge\api\template\Template;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\event\NetworkPacketReceiveEvent;
use pocketcloud\cloudbridge\network\Network;
use pocketcloud\cloudbridge\network\packet\handler\decoder\PacketDecoder;
use pocketcloud\cloudbridge\network\packet\impl\normal\CommandSendPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\DisconnectPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\KeepALivePacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\LocalPlayerRegisterPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\LocalPlayerUnregisterPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\LocalPlayerUpdatePacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\LocalServerRegisterPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\LocalServerUnregisterPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\LocalServerUpdatePacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\LocalTemplateRegisterPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\LocalTemplateUnregisterPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\LocalTemplateUpdatePacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\NotifyPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\PlayerKickPacket;
use pocketcloud\cloudbridge\network\packet\impl\normal\PlayerTextPacket;
use pocketcloud\cloudbridge\network\packet\impl\types\DisconnectReason;
use pocketcloud\cloudbridge\network\packet\impl\types\TextType;
use pocketcloud\cloudbridge\network\packet\listener\PacketListener;
use pocketcloud\cloudbridge\network\packet\ResponsePacket;
use pocketcloud\cloudbridge\network\request\RequestManager;
use pocketcloud\cloudbridge\utils\Message;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

class PacketHandler {
    use SingletonTrait;

    public function __construct() {
        self::setInstance($this);
        PacketListener::getInstance()->register(DisconnectPacket::class, function(DisconnectPacket $packet): void {
            if ($packet->getDisconnectReason() === DisconnectReason::CLOUD_SHUTDOWN()) {
                \GlobalLogger::get()->emergency("§4Cloud was stopped! Shutdown...");
                Server::getInstance()->shutdown();
            } else {
                \GlobalLogger::get()->emergency("§4Server shutdown was ordered by the cloud! Shutdown...");
                Server::getInstance()->shutdown();
            }
        });

        PacketListener::getInstance()->register(KeepALivePacket::class, function(KeepALivePacket $packet): void {
            CloudBridge::getInstance()->lastKeepALiveCheck = microtime(true);
            Network::getInstance()->sendPacket(new KeepALivePacket());
        });

        PacketListener::getInstance()->register(CommandSendPacket::class, fn(CommandSendPacket $packet) => Server::getInstance()->dispatchCommand(new ConsoleCommandSender(Server::getInstance(), Server::getInstance()->getLanguage()), $packet->getCommandLine()));

        PacketListener::getInstance()->register(PlayerTextPacket::class, function(PlayerTextPacket $packet): void {
            if ($packet->getPlayer() == "*") {
                if ($packet->getTextType() === TextType::MESSAGE()) Server::getInstance()->broadcastMessage($packet->getMessage());
                else if ($packet->getTextType() === TextType::POPUP()) Server::getInstance()->broadcastPopup($packet->getMessage());
                else if ($packet->getTextType() === TextType::TIP()) Server::getInstance()->broadcastTip($packet->getMessage());
                else if ($packet->getTextType() === TextType::TITLE()) Server::getInstance()->broadcastTitle($packet->getMessage());
                else if ($packet->getTextType() === TextType::ACTION_BAR()) Server::getInstance()->broadcastPackets(Server::getInstance()->getOnlinePlayers(), [SetTitlePacket::actionBarMessage($packet->getMessage())]);
            } else if (($player = Server::getInstance()->getPlayerExact($packet->getPlayer())) !== null) {
                if ($packet->getTextType() === TextType::MESSAGE()) $player->sendMessage($packet->getMessage());
                else if ($packet->getTextType() === TextType::POPUP()) $player->sendPopup($packet->getMessage());
                else if ($packet->getTextType() === TextType::TIP()) $player->sendTip($packet->getMessage());
                else if ($packet->getTextType() === TextType::TITLE()) $player->sendTitle($packet->getMessage());
                else if ($packet->getTextType() === TextType::ACTION_BAR()) $player->sendActionBarMessage($packet->getMessage());
            }
        });

        PacketListener::getInstance()->register(LocalPlayerRegisterPacket::class, function(LocalPlayerRegisterPacket $packet): void {
            if (($player = CloudPlayer::fromArray($packet->getPlayer())) !== null) Registry::registerPlayer($player);
        });

        PacketListener::getInstance()->register(LocalPlayerUpdatePacket::class, fn(LocalPlayerUpdatePacket $packet) => Registry::updatePlayer($packet->getPlayer(), $packet->getNewServer()));
        PacketListener::getInstance()->register(LocalPlayerUnregisterPacket::class, fn(LocalPlayerUnregisterPacket $packet) => Registry::unregisterPlayer($packet->getPlayer()));

        PacketListener::getInstance()->register(LocalTemplateRegisterPacket::class, function(LocalTemplateRegisterPacket $packet): void {
            if (($template = Template::fromArray($packet->getTemplate())) !== null) Registry::registerTemplate($template);
        });

        PacketListener::getInstance()->register(LocalTemplateUpdatePacket::class, fn(LocalTemplateUpdatePacket $packet) => Registry::updateTemplate($packet->getTemplate(), $packet->getNewData()));
        PacketListener::getInstance()->register(LocalTemplateUnregisterPacket::class, fn(LocalTemplateUnregisterPacket $packet) => Registry::unregisterTemplate($packet->getTemplate()));

        PacketListener::getInstance()->register(LocalServerRegisterPacket::class, function(LocalServerRegisterPacket $packet): void {
            if (($server = CloudServer::fromArray($packet->getServer())) !== null) Registry::registerServer($server);
        });

        PacketListener::getInstance()->register(LocalServerUpdatePacket::class, fn(LocalServerUpdatePacket $packet) => Registry::updateServer($packet->getServer(), $packet->getNewStatus()));
        PacketListener::getInstance()->register(LocalServerUnregisterPacket::class, fn(LocalServerUnregisterPacket $packet) => Registry::unregisterServer($packet->getServer()));

        PacketListener::getInstance()->register(NotifyPacket::class, function(NotifyPacket $packet): void {
            foreach ($packet->getPlayers() as $player) {
                if (($player = Server::getInstance()->getPlayerExact($player)) !== null) {
                    if ($player->hasPermission("pocketcloud.notify.receive")) {
                        $player->sendMessage($packet->getMessage());
                    }
                }
            }
        });

        PacketListener::getInstance()->register(PlayerKickPacket::class, function(PlayerKickPacket $packet): void {
            if (($player = Server::getInstance()->getPlayerExact($packet->getPlayer())) !== null) {
                if ($packet->getReason() == "MAINTENANCE") {
                    if (!$player->hasPermission("pocketcloud.maintenance.bypass")) $player->kick(Message::parse(Message::TEMPLATE_MAINTENANCE));
                } else {
                    $player->kick($packet->getReason());
                }
            }
        });
    }

    public function handle(string $buffer) {
        if (($packet = PacketDecoder::decode($buffer)) !== null) {
            $ev = new NetworkPacketReceiveEvent($packet);
            $ev->call();

            if ($ev->isCancelled()) return;

            PacketListener::getInstance()->call($packet);

            if ($packet instanceof ResponsePacket) {
                RequestManager::getInstance()->callThen($packet);
                RequestManager::getInstance()->removeRequest($packet->getRequestId());
            }
        } else {
            \GlobalLogger::get()->debug("§cReceived an invalid packet.");
            \GlobalLogger::get()->debug("§e" . $buffer);
        }
    }
}