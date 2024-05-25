<?php

namespace pocketcloud\cloudbridge\module\npc;

use Exception;
use pocketcloud\cloudbridge\api\server\CloudServer;
use pocketcloud\cloudbridge\module\npc\group\TemplateGroup;
use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\template\Template;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\event\npc\CloudNPCSpawnEvent;
use pocketcloud\cloudbridge\event\npc\CloudNPCUpdateEvent;
use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\module\npc\skin\CustomSkinModel;
use pocketcloud\cloudbridge\module\npc\task\CloudNPCTickTask;
use pocketcloud\cloudbridge\util\SkinSaver;
use pocketcloud\cloudbridge\util\Utils;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\Server;
use pocketmine\world\Position;

class CloudNPC {

    private ?Human $entity = null;
    private ?CloudNPCTickTask $tickTask = null;

    public function __construct(
        private readonly Template|TemplateGroup $template,
        private readonly Position $position,
        private readonly string $creator,
        private readonly ?CustomSkinModel $customSkinModel
    ) {}

    /** @internal */
    public function tick(): void {
        if ($this->entity !== null) {
            if ($this->checkExistence()) {
                $nameTag = Language::current()->translate("inGame.cloudnpc.name_tag" . ($this->isTemplateMaintenance() ? ".maintenance" : ""), $this->getTemplateOnlineCount(), (!$this->hasTemplateGroup() ? $this->template->getName() : $this->template->getDisplayName()));
                if ($this->entity->getNameTag() !== $nameTag) {
                    ($ev = new CloudNPCUpdateEvent($this, $this->entity->getNameTag(), $nameTag))->call();
                    if ($ev->isCancelled()) return;
                    $this->entity->setNameTag($ev->getNewNameTag());
                }

                if ($this->entity->getPosition()->distance($this->position) >= 0.5) {
                    $this->entity->teleport($this->position);
                }
            } else {
                $this->despawnEntity();
                $this->tickTask->getHandler()?->cancel();
            }
        }
    }

    public function spawnEntity(): void {
        if (CloudNPCModule::get()->isEnabled()) {
            try {
                $skin = SkinSaver::get($this->creator);
                if ($this->getSkinModel() !== null && ($tempSkin = $this->customSkinModel->createSkin()) !== null) $skin = $tempSkin;
                if ($this->entity !== null && !$this->entity->isClosed()) $this->despawnEntity();
                $yaw = ($this->position instanceof Location ? $this->position->getYaw() : lcg_value() * 360);
                $pitch = ($this->position instanceof Location ? $this->position->getPitch() : 0);
                $this->entity = new Human(Location::fromObject($this->position->add(0, 2, 0), $this->position->getWorld(), $yaw, $pitch), $skin);
                $this->entity->setCanSaveWithChunk(false);
                $this->entity->setNoClientPredictions();
                $this->entity->setNameTagAlwaysVisible();
                ($ev = new CloudNPCSpawnEvent($this, $this->entity))->call();
                if ($ev->isCancelled()) return;
                $this->entity->spawnToAll();
                CloudBridge::getInstance()->getScheduler()->scheduleRepeatingTask($this->tickTask = new CloudNPCTickTask($this), 10);
            } catch (Exception $e) {
                CloudNPCModule::get()->getLogger()->logException($e);
            }
        }
    }

    public function despawnEntity(): void {
        if ($this->entity !== null) {
            $this->entity->flagForDespawn();
            $this->entity = null;
            $this->tickTask->getHandler()?->cancel();
        }
    }

    /** @return array<CloudServer> */
    public function getServers(): array {
        $servers = [];
        $templates = $this->hasTemplateGroup() ? $this->template->getTemplates() : [$this->template->getName()];
        foreach ($templates as $template) {
            if (($template = CloudAPI::getInstance()->getTemplateByName($template)) !== null) {
                foreach (CloudAPI::getInstance()->getServersByTemplate($template) as $server) $servers[] = $server;
            }
        }

        return $servers;
    }

    public function getEntity(): ?Human {
        return $this->entity;
    }

    public function getTemplate(): Template|TemplateGroup {
        return $this->template;
    }
    
    public function getTemplateOnlineCount(): int {
        $onlineCount = 0;
        foreach ($this->getServers() as $server) $onlineCount += count($server->getCloudPlayers());
        return $onlineCount;
    }

    public function isTemplateMaintenance(): bool {
        if ($this->hasTemplateGroup()) {
            $i = 0;
            foreach ($this->getTemplate()->getTemplates() as $template) {
                if (($template = CloudAPI::getInstance()->getTemplateByName($template)) !== null && $template->isMaintenance()) {
                    $i++;
                }
            }

            return $i == count($this->getTemplate()->getTemplates());
        }

        return $this->template->isMaintenance();
    }

    public function getPosition(): Position {
        return $this->position;
    }

    public function getCreator(): string {
        return $this->creator;
    }

    public function getSkinModel(): ?CustomSkinModel {
        return $this->customSkinModel;
    }

    public function toArray(): array {
        if ($this->hasTemplateGroup()) return [
            "group_id" => $this->template->getId(),
            "position" => Utils::convertToString($this->position),
            "creator" => $this->creator,
            "skin_model" => $this->customSkinModel?->getId()
        ];

        return [
            "template" => $this->template->getName(),
            "position" => Utils::convertToString($this->position),
            "creator" => $this->creator,
            "skin_model" => $this->customSkinModel?->getId()
        ];
    }

    public function checkExistence(): bool {
        return CloudNPCModule::get()->checkCloudNPC($this->position);
    }

    public function hasTemplateGroup(): bool {
        return $this->template instanceof TemplateGroup;
    }

    public static function fromArray(array $data): ?CloudNPC {
        if (Utils::containKeys($data, "group_id", "position", "creator", "skin_model") || Utils::containKeys($data, "template", "position", "creator", "skin_model")) {
            if ($data["skin_model"] !== null) $data["skin_model"] = CloudNPCModule::get()->getSkinModel($data["skin_model"]);

            if (isset($data["group_id"])) {
                if (($group = CloudNPCModule::get()->getTemplateGroup($data["group_id"])) !== null) {
                    /** @var Position $position */
                    if (($position = Utils::convertToVector($data["position"])) instanceof Position) {
                        return new CloudNPC($group, $position, $data["creator"], $data["skin_model"]);
                    }
                }
                return null;
            }

            /** @var Position $position */
            $position = Utils::convertToVector($data["position"]);
            if (($template = CloudAPI::getInstance()->getTemplateByName($data["template"])) !== null && $position instanceof Position) {
                return new CloudNPC($template, $position, $data["creator"], $data["skin_model"]);
            }
        }
        return null;
    }
}