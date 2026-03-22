<?php

namespace pocketcloud\cloud\bridge\module\impl\npc;

use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\object\server\util\ServerStatus;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\event\npc\CloudNPCSpawnEvent;
use pocketcloud\cloud\bridge\event\npc\CloudNPCUpdateEvent;
use pocketcloud\cloud\bridge\language\Language;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\module\impl\npc\group\TemplateGroup;
use pocketcloud\cloud\bridge\module\impl\npc\skin\CustomSkinModel;
use pocketcloud\cloud\bridge\player\PlayerSession;
use pocketcloud\cloud\bridge\util\CloudEnvironmentConfig;
use pocketcloud\cloud\bridge\util\misc\Writeable;
use pocketcloud\cloud\bridge\util\SkinSaver;
use pocketcloud\cloud\bridge\util\Utils;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\NeverSavedWithChunkEntity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use r3pt1s\forms\builder\MenuFormBuilder;
use r3pt1s\forms\element\menu\MenuOption;

final class CloudNPC extends Human implements Writeable, NeverSavedWithChunkEntity {

    public function __construct(
        private readonly Template|TemplateGroup $template,
        private readonly Location $originLocation,
        private readonly string $creator,
        private readonly ?CustomSkinModel $customSkinModel,
        private readonly bool $headRotation
    ) {
        $skin = SkinSaver::get($this->creator);
        if ($this->getSkinModel() !== null && ($tempSkin = $this->customSkinModel->createSkin()) !== null) $skin = $tempSkin;

        parent::__construct(
            Location::fromObject($this->originLocation, $this->originLocation->getWorld(), $this->originLocation->getYaw(), $this->originLocation->getPitch()),
            $skin
        );
    }

    /**
     * This method should not be used - cloudnpcs are saved within a file inside the plugin_data/CloudBridge/ folfer
     * @param bool $value
     * @return void
     */
    public function setCanSaveWithChunk(bool $value = false): void {
        parent::setCanSaveWithChunk(false);
    }

    public function setHasGravity(bool $v = false): void {
        parent::setHasGravity(false);
    }

    protected function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);
        $this->setCanSaveWithChunk();
        $this->setHasGravity();
        $this->setNameTagAlwaysVisible();

        ($ev = new CloudNPCSpawnEvent($this, $this))->call();
        if ($ev->isCancelled()) $this->flagForDespawn();
    }

    public function attack(EntityDamageEvent $source): void {
        $source->cancel();
        if ($source instanceof EntityDamageByEntityEvent) {
            $damager = $source->getDamager();
            if ($damager instanceof Player) {
                if (PlayerSession::get($damager)->isAwaitNpcRemoval()) {
                    PlayerSession::get($damager)->setAwaitNpcRemoval(false);
                    if (CloudNPCModule::get()->removeCloudNPC($this)) {
                        $damager->sendMessage(LanguageKey::INGAME_CLOUDNPC_REMOVED());
                    } else {
                        $damager->sendMessage(LanguageKey::INGAME_PREFIX() . "§cAn error occurred while removing the NPC.");
                    }
                    return;
                }

                $servers = array_values(array_filter(
                    $this->getServers(),
                    fn(CloudServer $s) => $s->getName() !== CloudEnvironmentConfig::getServerName() &&
                        $s->getServerStatus() === ServerStatus::ONLINE &&
                        !($s->getTemplate()->isMaintenance() && !$damager->hasPermission("pocketcloud.bypass.maintenance"))
                ));

                $name = $this->hasTemplateGroup() ? $this->getTemplate()->getDisplayName() : $this->getTemplate()->getName();

                $options = count($servers) === 0
                    ? [new MenuOption(LanguageKey::INGAME_UI_CLOUDNPC_CHOOSE_SERVER_NO_SERVER())]
                    : array_map(
                        fn(CloudServer $s) => new MenuOption(LanguageKey::INGAME_UI_CLOUDNPC_CHOOSE_SERVER_BUTTON_SERVER()->translate(
                            [
                                $s->getName(),
                                $s->getPlayerCount(),
                                $s->getServerData()->getMaxPlayers()
                            ]
                        )),
                        $servers
                    );

                $damager->sendForm(MenuFormBuilder::create(LanguageKey::INGAME_UI_CLOUDNPC_CHOOSE_SERVER_TITLE()->translate([$name]), LanguageKey::INGAME_UI_CLOUDNPC_CHOOSE_SERVER_TEXT()->translate([count($servers), $name]))
                    ->elements($options)
                    ->onSubmit(function (Player $player, int $index) use($servers): void {
                        $server = $servers[$index] ?? null;
                        if (!$server instanceof CloudServer) return;

                        $player->sendMessage(LanguageKey::INGAME_SERVER_CONNECT()->translate([$server->getName()]));
                        if (!CloudPlayerProvider::provider()->transfer($player, $server)) {
                            $player->sendMessage(LanguageKey::INGAME_SERVER_CONNECT_FAILED()->translate([$server->getName()]));
                        }
                    })
                    ->build()
                );
            }
        }
    }

    protected function entityBaseTick(int $tickDiff = 1): bool {
        if (!$this->checkExistence()) {
            $this->flagForDespawn();
            return true;
        }

        $hasUpd = parent::entityBaseTick($tickDiff);
        $nameTag = Language::current()->translate("inGame.cloudnpc.name_tag" . ($this->isTemplateMaintenance() ? ".maintenance" : ""), [$this->getTemplateOnlineCount(), (!$this->hasTemplateGroup() ? $this->template->getName() : $this->template->getDisplayName())]);
        if ($this->getNameTag() !== $nameTag) {
            ($ev = new CloudNPCUpdateEvent($this, $this->getNameTag(), $nameTag))->call();
            if (!$ev->isCancelled()) $this->setNameTag($ev->getNewNameTag());
        }

        if ($this->getLocation()->distanceSquared($this->originLocation) >= 0.5) {
            $this->teleport($this->originLocation);
        }

        return $hasUpd;
    }

    public function checkExistence(): bool {
        return CloudNPCModule::get()->checkCloudNPC($this->originLocation);
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

    public function getOriginPosition(): Location {
        return $this->originLocation;
    }

    public function getSkinModel(): ?CustomSkinModel {
        return $this->customSkinModel;
    }

    public function getCreator(): string {
        return $this->creator;
    }

    public function isHeadRotation(): bool {
        return $this->headRotation;
    }

    public function isVisibleTo(Player $player): bool {
        return isset($this->hasSpawned[spl_object_id($player)]) && $this->isAlive();
    }

    public function write(): array {
        if ($this->hasTemplateGroup()) return [
            "group_id" => $this->template->getId(),
            "position" => Utils::convertToString($this->originLocation),
            "creator" => $this->creator,
            "skin_model" => $this->customSkinModel?->getId(),
            "head_rotation" => $this->headRotation
        ];

        return [
            "template" => $this->template->getName(),
            "position" => Utils::convertToString($this->originLocation),
            "creator" => $this->creator,
            "skin_model" => $this->customSkinModel?->getId(),
            "head_rotation" => $this->headRotation
        ];
    }

    public static function read(array $data): ?CloudNPC {
        if (Utils::containKeys($data, "group_id", "position", "creator", "skin_model") || Utils::containKeys($data, "template", "position", "creator", "skin_model")) {
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
            if (($template = TemplateProvider::provider()->get($data["template"])) !== null && $position instanceof Location) {
                return new CloudNPC($template, $position, $data["creator"], $data["skin_model"], $headRotation);
            }
        }
        return null;
    }
}