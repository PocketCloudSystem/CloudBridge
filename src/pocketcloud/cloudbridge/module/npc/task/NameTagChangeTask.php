<?php

namespace pocketcloud\cloudbridge\module\npc\task;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\event\npc\CloudNPCUpdateEvent;
use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\module\npc\CloudNPC;
use pocketcloud\cloudbridge\module\npc\CloudNPCManager;
use pocketmine\entity\Human;
use pocketmine\scheduler\Task;

class NameTagChangeTask extends Task {

    public function __construct(private CloudNPC $cloudNPC, private Human $entity) {}

    public function onRun(): void {
        if ($this->isAlive()) {
            $nameTag = Language::current()->translate("inGame.cloudnpc.name_tag", count(CloudAPI::getInstance()->getPlayersOfTemplate($this->cloudNPC->getTemplate())), $this->cloudNPC->getTemplate()->getName());
            if ($this->entity->getNameTag() !== $nameTag) {
                ($ev = new CloudNPCUpdateEvent($this->cloudNPC, $this->entity->getNameTag(), $nameTag))->call();
                if ($ev->isCancelled()) return;
                $this->entity->setNameTag($ev->getNewNameTag());
            }

            if (!$this->entity->isNameTagVisible()) $this->entity->setNameTagVisible(true);
            if (!$this->entity->isNameTagAlwaysVisible()) $this->entity->setNameTagAlwaysVisible(true);
        } else {
            if (!$this->entity->isClosed()) $this->entity->flagForDespawn();
            $this->getHandler()->cancel();
        }
    }

    private function isAlive(): bool {
        return CloudNPCManager::getInstance()->getCloudNPC($this->cloudNPC->getPosition()) !== null;
    }
}