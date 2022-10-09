<?php

namespace pocketcloud\cloudbridge\module\sign;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\config\ModulesConfig;
use pocketcloud\cloudbridge\event\CloudSignAddEvent;
use pocketcloud\cloudbridge\event\CloudSignRemoveEvent;
use pocketcloud\cloudbridge\module\sign\listener\SignListener;
use pocketcloud\cloudbridge\module\sign\task\CloudSignTask;
use pocketcloud\cloudbridge\utils\Utils;
use pocketmine\math\Vector3;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;

class CloudSignManager {
    use SingletonTrait;

    /** @var array<CloudSign> */
    private array $signs = [];
    /** @var array<CloudSign> */
    private array $usingServerNames = [];

    public function __construct() {
        self::setInstance($this);
        if (ModulesConfig::getInstance()->isSignModule()) {
            CloudBridge::getInstance()->registerPermission("pocketcloud.cloudsign.add", "pocketcloud.cloudsign.remove");
            Server::getInstance()->getPluginManager()->registerEvents(new SignListener(), CloudBridge::getInstance());
        }
    }

    public function load() {
        if (!ModulesConfig::getInstance()->isSignModule()) return;
        foreach ($this->getCloudSignConfig()->getAll() as $positionString => $cloudSign) {
            if (!Utils::containKeys($cloudSign, "Template", "Position", "World")) continue;
            /** @var Vector3 $position */
            if (($position = Utils::convertToVector($positionString)) instanceof Vector3) {
                if (($world = Server::getInstance()->getWorldManager()->getWorldByName($cloudSign["World"])) !== null) {
                    if (($template = CloudAPI::getInstance()->getTemplateByName($cloudSign["Template"])) !== null) {
                        $this->signs[$positionString] = new CloudSign(
                            $template,
                            Position::fromObject($position, $world)
                        );
                    }
                }
            }
        }
        CloudBridge::getInstance()->getScheduler()->scheduleRepeatingTask(new CloudSignTask(), 20);
    }

    public function addCloudSign(CloudSign $cloudSign) {
        if (!ModulesConfig::getInstance()->isSignModule()) return;
        $ev = new CloudSignAddEvent($cloudSign);
        $ev->call();
        if ($ev->isCancelled()) return;

        $positionString = Utils::convertToString($cloudSign->getPosition()->asVector3()) . ":" . $cloudSign->getPosition()->getWorld()->getFolderName();
        $cfg = $this->getCloudSignConfig();
        $cfg->set($positionString, [
            "Template" => $cloudSign->getTemplate()->getName(),
            "Position" => $positionString,
            "World" => $cloudSign->getPosition()->getWorld()->getFolderName()
        ]);
        $cfg->save();

        $this->signs[$positionString] = $cloudSign;
    }

    public function removeCloudSign(CloudSign $cloudSign) {
        if (!ModulesConfig::getInstance()->isSignModule()) return;
        $ev = new CloudSignRemoveEvent($cloudSign);
        $ev->call();
        if ($ev->isCancelled()) return;

        $positionString = Utils::convertToString($cloudSign->getPosition()->asVector3()) . ":" . $cloudSign->getPosition()->getWorld()->getFolderName();
        $cfg = $this->getCloudSignConfig();
        $cfg->remove($positionString);
        $cfg->save();

        if (isset($this->signs[$positionString])) unset($this->signs[$positionString]);
        if ($cloudSign->hasUsingServer()) $this->removeUsingServerName($cloudSign->getUsingServer()->getName());
    }

    public function addUsingServerName(string $name, CloudSign $cloudSign): bool {
        if (isset($this->usingServerNames[$name])) return false;
        $this->usingServerNames[$name] = $cloudSign;
        return true;
    }

    public function removeUsingServerName(string $name): bool {
        if (!isset($this->usingServerNames[$name])) return false;
        unset($this->usingServerNames[$name]);
        return true;
    }

    public function getCloudSign(Position $position): ?CloudSign {
        return $this->signs[Utils::convertToString($position->asVector3()) . ":" . $position->getWorld()->getFolderName()] ?? null;
    }

    public function isUsingServerName(string $name): bool {
        return isset($this->usingServerNames[$name]);
    }

    private function getCloudSignConfig(): Config {
        return new Config(CloudBridge::getInstance()->getDataFolder() . "cloudSigns.json", 1);
    }

    public function getCloudSigns(): array {
        return $this->signs;
    }
}