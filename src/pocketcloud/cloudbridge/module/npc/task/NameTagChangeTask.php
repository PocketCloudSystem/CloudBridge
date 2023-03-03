<?php

namespace pocketcloud\cloudbridge\module\npc\task;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\event\CloudNPCUpdateEvent;
use pocketcloud\cloudbridge\module\npc\CloudNPC;
use pocketcloud\cloudbridge\module\npc\CloudNPCManager;
use pocketcloud\cloudbridge\utils\Message;
use pocketmine\entity\Human;
use pocketmine\scheduler\Task;

class NameTagChangeTask extends Task {

    public function __construct(private CloudNPC $cloudNPC, private Human $entity) {}

    public function onRun(): void {
        if ($this->isAlive()) {
            $nameTag = Message::parse(Message::NPC_NAME_TAG, [count(CloudAPI::getInstance()->getPlayersOfTemplate($this->cloudNPC->getTemplate())), $this->cloudNPC->getTemplate()->getName()])->getMessage();
            if ($this->entity->getNameTag() !== $nameTag) {
                $ev = new CloudNPCUpdateEvent($this->cloudNPC, $this->entity->getNameTag(), $nameTag);
                $ev->call();
                if ($ev->isCancelled()) return;
                $this->entity->setNameTag($ev->getNewNameTag());
            }
            if (!$this->entity->isNameTagVisible()) $this->entity->setNameTagVisible(true);
            if (!$this->entity->isNameTagAlwaysVisible()) $this->entity->setNameTagAlwaysVisible(true);
        } else {
            if (!$this->entity->isClosed()) $this->entity->close();
            $this->getHandler()->cancel();
        }
    }

    private function isAlive(): bool {
        return CloudNPCManager::getInstance()->getCloudNPC($this->cloudNPC->getPosition()) !== null;
    }
}