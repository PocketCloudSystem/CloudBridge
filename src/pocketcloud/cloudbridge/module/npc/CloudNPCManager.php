<?php

namespace pocketcloud\cloudbridge\module\npc;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\module\BaseModuleTrait;
use pocketcloud\cloudbridge\module\npc\command\CloudNPCCommand;
use pocketcloud\cloudbridge\module\npc\task\NameTagChangeTask;
use pocketcloud\cloudbridge\event\npc\CloudNPCCreateEvent;
use pocketcloud\cloudbridge\event\npc\CloudNPCRemoveEvent;
use pocketcloud\cloudbridge\event\npc\CloudNPCSpawnEvent;
use pocketcloud\cloudbridge\util\SkinSaver;
use pocketcloud\cloudbridge\util\Utils;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;

class CloudNPCManager {
    use SingletonTrait, BaseModuleTrait;

    /** @var array<CloudNPC>  */
    private array $npcs = [];
    private array $entities = [];

    public function __construct() {
        self::setInstance($this);
    }

    public function load() {
        if (!self::isEnabled()) return;
        foreach ($this->getNPCConfig()->getAll() as $positionString => $cloudNPC) {
            if (!Utils::containKeys($cloudNPC, "Template", "Creator", "Position")) continue;
            /** @var Position $position */
            if (($position = Utils::convertToVector($cloudNPC["Position"])) instanceof Position) {
                if (($template = CloudAPI::getInstance()->getTemplateByName($cloudNPC["Template"])) !== null) {
                    $this->npcs[$positionString] = new CloudNPC(
                        $template,
                        $position,
                        $cloudNPC["Creator"]
                    );
                }
            }
        }
    }

    public function unload() {
        if (self::isEnabled()) return;
        $this->npcs = [];
    }

    public function addCloudNPC(CloudNPC $cloudNPC) {
        if (!self::isEnabled()) return;
        ($ev = new CloudNPCCreateEvent($cloudNPC))->call();
        if ($ev->isCancelled()) return;

        $positionString = Utils::convertToString($cloudNPC->getPosition());
        $cfg = $this->getNPCConfig();
        $cfg->set($positionString, [
            "Template" => $cloudNPC->getTemplate()->getName(),
            "Creator" => $cloudNPC->getCreator(),
            "Position" => $positionString
        ]);
        $cfg->save();

        $this->spawnCloudNPC($cloudNPC);
        if (!isset($this->npcs[$positionString])) $this->npcs[$positionString] = $cloudNPC;
    }

    public function removeCloudNPC(CloudNPC $cloudNPC) {
        if (!self::isEnabled()) return;
        ($ev = new CloudNPCRemoveEvent($cloudNPC))->call();
        if ($ev->isCancelled()) return;

        $positionString = Utils::convertToString($cloudNPC->getPosition());
        $cfg = $this->getNPCConfig();
        $cfg->remove($positionString);
        $cfg->save();

        if (isset($this->npcs[$positionString])) unset($this->npcs[$positionString]);
    }

    public function spawnCloudNPC(CloudNPC $cloudNPC) {
        if (!self::isEnabled()) return;
        if (($skin = SkinSaver::get($cloudNPC->getCreator())) !== null) {
            $positionString = Utils::convertToString($cloudNPC->getPosition());
            if (isset($this->entities[$positionString])) {
                /** @var Human $entity */
                if (($entity = $this->entities[$positionString]) !== null) $entity->close();
                unset($this->entities[$positionString]);
            }
            $position = $cloudNPC->getPosition();
            $yaw = ($position instanceof Location ? $position->getYaw() : lcg_value() * 360);
            $pitch = ($position instanceof Location ? $position->getPitch() : 0);
            $human = new Human(Location::fromObject($cloudNPC->getPosition(), null, $yaw, $pitch), $skin);
            $human->setCanSaveWithChunk(false);
            ($ev = new CloudNPCSpawnEvent($cloudNPC, $human))->call();
            if ($ev->isCancelled()) return;
            $human->spawnToAll();
            $this->entities[$positionString] = $human;
            CloudBridge::getInstance()->getScheduler()->scheduleRepeatingTask(new NameTagChangeTask($cloudNPC, $human), 10);
        }
    }

    public function spawnCloudNPCs() {
        if (!self::isEnabled()) return;
        foreach ($this->getCloudNPCs() as $npc) {
            $this->spawnCloudNPC($npc);
        }
    }

    public function checkCloudNPC(Position $position): bool {
        return $this->getNPCConfig()->exists(Utils::convertToString($position));
    }

    public function getCloudNPC(Position $position): ?CloudNPC {
        return $this->npcs[Utils::convertToString($position)] ?? null;
    }

    public function getCloudNPCEntity(CloudNPC $cloudNPC): ?Human {
        return $this->entities[Utils::convertToString($cloudNPC->getPosition())] ?? null;
    }

    private function getNPCConfig(): Config {
        return new Config(CloudBridge::getInstance()->getDataFolder() . "cloudNpcs.json", 1);
    }

    public function getCloudNPCs(): array {
        return $this->npcs;
    }

    public function getCloudEntities(): array {
        return $this->entities;
    }

    public static function enable(): void {
        if (self::isEnabled()) return;
        self::setEnabled(true);
        Server::getInstance()->getCommandMap()->register("cloudNpcModule", new CloudNPCCommand());
        foreach (Server::getInstance()->getOnlinePlayers() as $player) $player->getNetworkSession()->syncAvailableCommands();
        self::getInstance()->load();
        self::getInstance()->spawnCloudNPCs();
    }

    public static function disable(): void {
        if (!self::isEnabled()) return;
        self::setEnabled(false);
        if (($command = Server::getInstance()->getCommandMap()->getCommand("cloudnpc")) !== null) {
            if ($command instanceof CloudNPCCommand) {
                Server::getInstance()->getCommandMap()->unregister($command);
            }
        }
        foreach (Server::getInstance()->getOnlinePlayers() as $player) $player->getNetworkSession()->syncAvailableCommands();
        self::getInstance()->unload();
    }
}
