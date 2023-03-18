<?php

namespace pocketcloud\cloudbridge\module\globalchat;

use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\config\ModulesConfig;
use pocketcloud\cloudbridge\event\GlobalChatEvent;
use pocketcloud\cloudbridge\module\globalchat\listener\GlobalChatListener;
use pocketcloud\cloudbridge\network\Network;
use pocketcloud\cloudbridge\network\packet\impl\normal\PlayerTextPacket;
use pocketcloud\cloudbridge\network\packet\impl\types\TextType;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

class GlobalChat implements Listener {
    use SingletonTrait;

    public function __construct() {
        self::setInstance($this);
        if (ModulesConfig::getInstance()->isGlobalChatModule()) Server::getInstance()->getPluginManager()->registerEvents($this, CloudBridge::getInstance());
    }

    public function onChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();
        $message = $event->getMessage();

        $ev = new GlobalChatEvent($player, $message, Server::getInstance()->getLanguage()->translateString("chat.type.text", [$player->getName(), $message]));
        $ev->call();
        if ($ev->isCancelled()) return;
        $event->cancel();
        Network::getInstance()->sendPacket(new PlayerTextPacket("*", $ev->getFormat(), TextType::MESSAGE()));
    }
}