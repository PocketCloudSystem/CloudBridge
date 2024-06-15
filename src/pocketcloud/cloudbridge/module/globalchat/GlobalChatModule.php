<?php

namespace pocketcloud\cloudbridge\module\globalchat;

use Exception;
use pocketcloud\cloudbridge\event\globalchat\GlobalChatEvent;
use pocketcloud\cloudbridge\module\BaseModule;
use pocketcloud\cloudbridge\network\Network;
use pocketcloud\cloudbridge\network\packet\impl\normal\PlayerTextPacket;
use pocketmine\event\EventPriority;
use pocketmine\event\HandlerListManager;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\RegisteredListener;
use pocketmine\Server;

final class GlobalChatModule extends BaseModule {

    private ?RegisteredListener $listener = null;

    public function onEnable(): void {
        try {
            $this->listener = $this->getServer()->getPluginManager()->registerEvent(PlayerChatEvent::class, function (PlayerChatEvent $event): void {
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
            }, EventPriority::HIGHEST, $this->getPlugin());
        } catch (Exception $exception) {
            $this->getLogger()->error("§cFailed to enable §8'§eGlobalChatModule§80'§c. Report this incident on the PocketCloud discord. §8(§9https://discord.com/invite/3HbPEpaE3T§8)");
            $this->getLogger()->logException($exception);
        }
    }

    public function onDisable(): void {
        if ($this->listener !== null) {
            try {
                HandlerListManager::global()->getListFor(PlayerChatEvent::class)->unregister($this->listener);
            } catch (Exception $exception) {
                $this->getLogger()->error("§cFailed to disable §8''§eGlobalChatModule§80'§c. Report this incident on the PocketCloud discord. §8(§9https://discord.com/invite/3HbPEpaE3T§8)");
                $this->getLogger()->logException($exception);
            }
        }
    }
}