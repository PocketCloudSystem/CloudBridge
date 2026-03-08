<?php

namespace pocketcloud\cloud\bridge\module\impl\npc;

use Exception;
use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\event\npc\CloudNPCSpawnEvent;
use pocketcloud\cloud\bridge\event\npc\CloudNPCUpdateEvent;
use pocketcloud\cloud\bridge\language\Language;
use pocketcloud\cloud\bridge\module\impl\npc\group\TemplateGroup;
use pocketcloud\cloud\bridge\module\impl\npc\skin\CustomSkinModel;
use pocketcloud\cloud\bridge\util\misc\Writeable;
use pocketcloud\cloud\bridge\util\SkinSaver;
use pocketcloud\cloud\bridge\util\Utils;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\world\Position;
use Random\Randomizer;

final class CloudNPC implements Writeable {

    private ?Human $entity = null;

    public function __construct(
        private readonly Template|TemplateGroup $template,
        private readonly Location $position,
        private readonly string $creator,
        private readonly ?CustomSkinModel $customSkinModel,
        private readonly bool $headRotation
    ) {}

    /** @internal */
    public function tick(): void {
        if ($this->entity !== null) {
            if ($this->checkExistence()) {
                $nameTag = Language::current()->translate("inGame.cloudnpc.name_tag" .
                    ($this->isTemplateMaintenance() ? ".maintenance" : ""), [$this->getTemplateOnlineCount(), (!$this->hasTemplateGroup() ? $this->template->getName() : $this->template->getDisplayName())]);
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
            }
        }
    }

    public function checkExistence(): bool {
        return CloudNPCModule::get()->checkCloudNPC($this->position);
    }

    public function isTemplateMaintenance(): bool {
        if ($this->hasTemplateGroup()) {
            $i = 0;
            foreach ($this->getTemplate()->getTemplates() as $template) {
                if (($template = TemplateProvider::provider()->get($template)) !== null && $template->isMaintenance()) {
                    $i++;
                }
            }

            return $i == count($this->getTemplate()->getTemplates());
        }

        return $this->template->isMaintenance();
    }

    public function hasTemplateGroup(): bool {
        return $this->template instanceof TemplateGroup;
    }

    public function getTemplate(): Template|TemplateGroup {
        return $this->template;
    }

    public function getTemplateOnlineCount(): int {
        $onlineCount = 0;
        foreach ($this->getServers() as $server) $onlineCount += $server->getPlayerCount();
        return $onlineCount;
    }

    /** @return array<CloudServer> */
    public function getServers(): array {
        $servers = [];
        $templates = $this->hasTemplateGroup() ? $this->template->getTemplates() : [$this->template->getName()];
        foreach ($templates as $template) {
            if (($template = TemplateProvider::provider()->get($template)) !== null) {
                foreach (CloudServerProvider::provider()->getAll($template) as $server) $servers[] = $server;
            }
        }

        return $servers;
    }

    public function getPosition(): Position {
        return $this->position;
    }

    public function despawnEntity(): void {
        if ($this->entity !== null) {
            $this->entity->flagForDespawn();
            $this->entity = null;
        }
    }

    public function spawnEntity(): void {
        if (CloudNPCModule::get()->isEnabled()) {
            try {
                $skin = SkinSaver::get($this->creator);
                if ($this->getSkinModel() !== null && ($tempSkin = $this->customSkinModel->createSkin()) !== null) $skin = $tempSkin;
                if ($this->entity !== null && !$this->entity->isClosed()) $this->despawnEntity();
                $yaw = ($this->position instanceof Location ? $this->position->getYaw() : new Randomizer()->getFloat(0, 1) * 360);
                $pitch = ($this->position instanceof Location ? $this->position->getPitch() : 0);
                $this->entity = new Human(Location::fromObject($this->position->add(0, 2, 0), $this->position->getWorld(), $yaw, $pitch), $skin);
                $this->entity->setCanSaveWithChunk(false);
                $this->entity->setNoClientPredictions();
                $this->entity->setNameTagAlwaysVisible();
                ($ev = new CloudNPCSpawnEvent($this, $this->entity))->call();
                if ($ev->isCancelled()) return;
                $this->entity->spawnToAll();
            } catch (Exception $e) {
                CloudNPCModule::get()->getLogger()->logException($e);
            }
        }
    }

    public function getSkinModel(): ?CustomSkinModel {
        return $this->customSkinModel;
    }

    public function getEntity(): ?Human {
        return $this->entity;
    }

    public function getCreator(): string {
        return $this->creator;
    }

    public function isHeadRotation(): bool {
        return $this->headRotation;
    }

    public function write(): array {
        if ($this->hasTemplateGroup()) return [
            "group_id" => $this->template->getId(),
            "position" => Utils::convertToString($this->position),
            "creator" => $this->creator,
            "skin_model" => $this->customSkinModel?->getId(),
            "head_rotation" => $this->headRotation
        ];

        return [
            "template" => $this->template->getName(),
            "position" => Utils::convertToString($this->position),
            "creator" => $this->creator,
            "skin_model" => $this->customSkinModel?->getId(),
            "head_rotation" => $this->headRotation
        ];
    }

    public static function read(array $data): ?CloudNPC {
        if (Utils::containKeys($data, "group_id", "position", "creator", "skin_model") ||
            Utils::containKeys($data, "template", "position", "creator", "skin_model")) {
            $headRotation = !isset($data["head_rotation"]) || !is_bool($data["head_rotation"]) || $data["head_rotation"];
            if ($data["skin_model"] !== null) $data["skin_model"] = CloudNPCModule::get()->getSkinModel($data["skin_model"]);
            if (isset($data["group_id"])) {
                if (($group = CloudNPCModule::get()->getTemplateGroup($data["group_id"])) !== null) {
                    /** @var Location $position */
                    if (($position = Utils::convertToVector($data["position"])) instanceof Location) {
                        return new CloudNPC($group, $position, $data["creator"], $data["skin_model"], $headRotation);
                    }
                }
                return null;
            }

            /** @var Location $position */
            $position = Utils::convertToVector($data["position"]);
            if (($template = TemplateProvider::provider()->get($data["template"])) !== null && $position instanceof Position) {
                return new CloudNPC($template, $position, $data["creator"], $data["skin_model"], $headRotation);
            }
        }
        return null;
    }
}