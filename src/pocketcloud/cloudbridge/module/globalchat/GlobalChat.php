<?php

namespace pocketcloud\cloudbridge\module\globalchat;

use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\event\globalchat\GlobalChatEvent;
use pocketcloud\cloudbridge\module\BaseModuleTrait;
use pocketcloud\cloudbridge\network\Network;
use pocketcloud\cloudbridge\network\packet\impl\normal\PlayerTextPacket;
use pocketmine\event\EventPriority;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\RegisteredListener;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

class GlobalChat implements Listener {
    use SingletonTrait, BaseModuleTrait;

    private static ?RegisteredListener $listener = null;

    public function __construct() {
        self::setInstance($this);
    }

    public static function enable() {
        if (self::isEnabled()) return;
        self::setEnabled(true);
        self::$listener = Server::getInstance()->getPluginManager()->registerEvent(PlayerChatEvent::class, function(PlayerChatEvent $event): void {
            $player = $event->getPlayer();
            $message = $event->getMessage();

            $ev = new GlobalChatEvent($player, $message, Server::getInstance()->getLanguage()->translateString("chat.type.text", [$player->getName(), $message]));
            $ev->call();
            if ($ev->isCancelled()) return;
            $event->cancel();
            Network::getInstance()->sendPacket(new PlayerTextPacket(
                "*",
                $ev->getFormat()
            ));
        }, EventPriority::HIGHEST, CloudBridge::getInstance());
    }

    public static function disable() {
        if (!self::isEnabled()) return;
        self::setEnabled(false);
        HandlerListManager::global()->getListFor(PlayerChatEvent::class)->unregister(self::$listener);
        self::$listener = null;
    }
}