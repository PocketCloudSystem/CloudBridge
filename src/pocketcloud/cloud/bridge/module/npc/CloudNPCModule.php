<?php

namespace pocketcloud\cloud\bridge\module\npc;

use JsonException;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\event\npc\CloudNPCCreateEvent;
use pocketcloud\cloud\bridge\event\npc\CloudNPCRemoveEvent;
use pocketcloud\cloud\bridge\module\BaseModule;
use pocketcloud\cloud\bridge\module\npc\command\CloudNPCCommand;
use pocketcloud\cloud\bridge\module\npc\command\TemplateGroupCommand;
use pocketcloud\cloud\bridge\module\npc\group\TemplateGroup;
use pocketcloud\cloud\bridge\module\npc\listener\NPCListener;
use pocketcloud\cloud\bridge\module\npc\skin\CustomSkinModel;
use pocketcloud\cloud\bridge\util\Utils;
use pocketmine\event\HandlerListManager;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\world\Position;

final class CloudNPCModule extends BaseModule {

    public array $npcDelay = [];
    public array $npcDetection = [];
    /** @var array<CloudNPC> */
    private array $npcs = [];
    /** @var array<TemplateGroup> */
    private array $templateGroups = [];
    /** @var array<CustomSkinModel> */
    private array $customSkinModels = [];
    private ?NPCListener $listener = null;

    protected function onEnable(): void {
        $this->listener = new NPCListener();
        $this->load();

        $this->getServer()->getCommandMap()->register("npcModule", new CloudNPCCommand());
        $this->getServer()->getCommandMap()->register("npcModule", new TemplateGroupCommand());
        foreach (Server::getInstance()->getOnlinePlayers() as $player) $player->getNetworkSession()->syncAvailableCommands();
    }

    protected function onDisable(): void {
        foreach ($this->npcs as $npc) $npc->despawnEntity();
        if ($this->listener !== null) HandlerListManager::global()->unregisterAll($this->listener);
        $this->listener = null;
        $this->npcDelay = [];
        $this->npcDetection = [];
        $this->npcs = [];
        $this->templateGroups = [];
        $this->customSkinModels = [];

        if (($cmd = $this->getServer()->getCommandMap()->getCommand("cloudnpc")) !== null) $this->getServer()->getCommandMap()->unregister($cmd);
        if (($cmd = $this->getServer()->getCommandMap()->getCommand("templategroup")) !== null) $this->getServer()->getCommandMap()->unregister($cmd);
        foreach (Server::getInstance()->getOnlinePlayers() as $player) $player->getNetworkSession()->syncAvailableCommands();
    }

    private function load(): void {
        foreach ($this->getGroupsConfig()->getAll() as $groupId => $groupData) {
            if (($templateGroup = TemplateGroup::fromArray($groupData)) !== null && $groupId == $groupData["id"]) {
                $this->templateGroups[$groupId] = $templateGroup;
            }
        }

        foreach ($this->getModelsConfig()->getAll() as $modelId => $modelData) {
            if (($model = CustomSkinModel::fromArray($modelData)) !== null && $modelId == $modelData["id"]) {
                $this->customSkinModels[$modelId] = $model;
            }
        }

        foreach ($this->getNPCConfig()->getAll() as $positionString => $npcData) {
            if (($cloudNPC = CloudNPC::fromArray($this->checkForMigration($npcData))) !== null && $positionString == $npcData["position"]) {
                $this->npcs[$positionString] = $cloudNPC;
                $cloudNPC->spawnEntity();
            }
        }
    }

    private function checkForMigration(array $data): array {
        foreach (["Template" => "template", "Position" => "position", "Creator" => "creator"] as $key => $newKey) {
            if (isset($data[$key])) {
                $data[$newKey] = $data[$key];
                unset($data[$key]);
            }
        }

        return $data;
    }

    public function addCloudNPC(CloudNPC $npc): bool {
        $positionString = Utils::convertToString($npc->getPosition());
        if (isset($this->npcs[$positionString])) return false;

        ($ev = new CloudNPCCreateEvent($npc))->call();
        if ($ev->isCancelled()) return false;

        try {
            ($cfg = $this->getNPCConfig())->set($positionString, $npc->toArray());
            $cfg->save();
        } catch (JsonException $exception) {
            CloudNPCModule::get()->getLogger()->logException($exception);
            return false;
        } finally {
            $this->npcs[$positionString] = $npc;
            $npc->spawnEntity();
        }

        return true;
    }

    public function addTemplateGroup(TemplateGroup $templateGroup): bool {
        if (isset($this->templateGroups[$templateGroup->getId()])) return false;

        try {
            ($cfg = $this->getGroupsConfig())->set($templateGroup->getId(), $templateGroup->toArray());
            $cfg->save();
        } catch (JsonException $exception) {
            CloudNPCModule::get()->getLogger()->logException($exception);
            return false;
        } finally {
            $this->templateGroups[$templateGroup->getId()] = $templateGroup;
        }

        return true;
    }

    public function addSkinModel(CustomSkinModel $model): bool {
        if (isset($this->customSkinModels[$model->getId()])) return false;

        try {
            ($cfg = $this->getModelsConfig())->set($model->getId(), $model->toArray());
            $cfg->save();
        } catch (JsonException $exception) {
            CloudNPCModule::get()->getLogger()->logException($exception);
            return false;
        } finally {
            $this->customSkinModels[$model->getId()] = $model;
        }

        return true;
    }

    public function spawnAll(): void {
        foreach ($this->npcs as $npc) $npc->spawnEntity();
    }

    public function removeCloudNPC(CloudNPC $npc): bool {
        $positionString = Utils::convertToString($npc->getPosition());
        if (!isset($this->npcs[$positionString])) return false;

        ($ev = new CloudNPCRemoveEvent($npc))->call();
        if ($ev->isCancelled()) return false;

        try {
            ($cfg = $this->getNPCConfig())->remove($positionString);
            $cfg->save();
        } catch (JsonException $exception) {
            CloudNPCModule::get()->getLogger()->logException($exception);
            return false;
        } finally {
            $npc->despawnEntity();
            unset($this->npcs[$positionString]);
        }

        return true;
    }

    public function removeTemplateGroup(TemplateGroup $templateGroup): bool {
        if (!isset($this->templateGroups[$templateGroup->getId()])) return false;

        try {
            ($cfg = $this->getGroupsConfig())->remove($templateGroup->getId());
            $cfg->save();
        } catch (JsonException $exception) {
            CloudNPCModule::get()->getLogger()->logException($exception);
            return false;
        } finally {
            unset($this->templateGroups[$templateGroup->getId()]);
            foreach (array_filter($this->npcs, fn (CloudNPC $npc) => $npc->hasTemplateGroup() && $npc->getTemplate()->getId() == $templateGroup->getId()) as $npc) {
                $this->removeCloudNPC($npc);
            }
        }

        return true;
    }

    public function removeSkinModel(CustomSkinModel $model): bool {
        if (!isset($this->customSkinModels[$model->getId()])) return false;

        try {
            ($cfg = $this->getModelsConfig())->remove($model->getId());
            $cfg->save();
        } catch (JsonException $exception) {
            CloudNPCModule::get()->getLogger()->logException($exception);
            return false;
        } finally {
            unset($this->customSkinModels[$model->getId()]);
            foreach (array_filter($this->npcs, fn (CloudNPC $npc) => $npc->getSkinModel() !== null && $npc->getSkinModel()->getId() == $model->getId()) as $npc) {
                $this->removeCloudNPC($npc);
            }
        }

        return true;
    }

    public function editTemplateGroup(TemplateGroup $templateGroup): bool {
        try {
            ($cfg = $this->getGroupsConfig())->set($templateGroup->getId(), $templateGroup->toArray());
            $cfg->save();
        } catch (JsonException $exception) {
            CloudNPCModule::get()->getLogger()->logException($exception);
            return false;
        } finally {
            foreach ($this->getCloudNPCsByGroup($templateGroup) as $npc) $npc->getTemplate()->applyData($templateGroup->toArray());
        }

        return true;
    }

    public function editSkinModel(CustomSkinModel $model): bool {
        try {
            ($cfg = $this->getModelsConfig())->set($model->getId(), $model->toArray());
            $cfg->save();
        } catch (JsonException $exception) {
            CloudNPCModule::get()->getLogger()->logException($exception);
            return false;
        }

        return true;
    }

    public function checkCloudNPC(Position $position): bool {
        return isset($this->npcs[Utils::convertToString($position)]);
    }

    public function getCloudNPC(Position $position): ?CloudNPC {
        return $this->npcs[Utils::convertToString($position)] ?? null;
    }

    /** @return array<CloudNPC> */
    public function getCloudNPCsByGroup(TemplateGroup $group): array {
        return array_filter($this->npcs, fn(CloudNPC $npc) => $npc->getTemplate() instanceof TemplateGroup && $npc->getTemplate()->getId() == $group->getId());
    }

    public function getTemplateGroup(string $id): ?TemplateGroup {
        return $this->templateGroups[$id] ?? null;
    }

    public function getSkinModel(string $id): ?CustomSkinModel {
        return $this->customSkinModels[$id] ?? null;
    }

    public function geTemplateGroupByDisplay(string $displayName): ?TemplateGroup {
        foreach ($this->templateGroups as $group) {
            if ($group->getDisplayName() == $displayName) return $group;
        }
        return null;
    }

    private function getNPCConfig(): Config {
        return new Config(CloudBridge::getInstance()->getDataFolder() . "cloudNpcs.json", 1);
    }

    private function getGroupsConfig(): Config {
        return new Config(CloudBridge::getInstance()->getDataFolder() . "cloudNpcGroups.json", 1);
    }

    private function getModelsConfig(): Config {
        return new Config(CloudBridge::getInstance()->getDataFolder() . "cloudNpcModels.json", 1);
    }

    public function getCloudNPCs(): array {
        return $this->npcs;
    }

    public function getTemplateGroups(): array {
        return $this->templateGroups;
    }

    public function getSkinModels(): array {
        return $this->customSkinModels;
    }
}