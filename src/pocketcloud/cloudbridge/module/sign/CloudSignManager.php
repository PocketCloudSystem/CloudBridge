<?php

namespace pocketcloud\cloudbridge\module\sign;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\event\sign\CloudSignAddEvent;
use pocketcloud\cloudbridge\event\sign\CloudSignRemoveEvent;
use pocketcloud\cloudbridge\module\BaseModuleTrait;
use pocketcloud\cloudbridge\module\sign\task\CloudSignTask;
use pocketcloud\cloudbridge\util\Utils;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\Position;

class CloudSignManager {
    use SingletonTrait, BaseModuleTrait;

    /** @var array<CloudSign> */
    private array $signs = [];
    /** @var array<CloudSign> */
    private array $usingServerNames = [];

    public function __construct() {
        self::setInstance($this);
    }

    public function load(): void {
        if (!self::isEnabled()) return;
        foreach ($this->getCloudSignConfig()->getAll() as $positionString => $cloudSign) {
            if (!Utils::containKeys($cloudSign, "Template", "Position", "World")) continue;
            if (Server::getInstance()->getWorldManager()->getWorldByName($cloudSign["World"]) !== null) {
                /** @var Position $position */
                if (($position = Utils::convertToVector($cloudSign["Position"])) instanceof Position) {
                    if (($template = CloudAPI::getInstance()->getTemplateByName($cloudSign["Template"])) !== null) {
                        $this->signs[$positionString] = new CloudSign(
                            $template,
                            $position
                        );
                    }
                }
            }
        }
        CloudBridge::getInstance()->getScheduler()->scheduleRepeatingTask(new CloudSignTask(), 20);
    }

    public function unload(): void {
        if (self::isEnabled()) return;
        $this->signs = [];
        $this->usingServerNames = [];
    }

    public function addCloudSign(CloudSign $cloudSign): void {
        if (!self::isEnabled()) return;
        ($ev = new CloudSignAddEvent($cloudSign))->call();
        if ($ev->isCancelled()) return;

        $positionString = Utils::convertToString($cloudSign->getPosition());
        $cfg = $this->getCloudSignConfig();
        $cfg->set($positionString, [
            "Template" => $cloudSign->getTemplate()->getName(),
            "Position" => $positionString,
            "World" => $cloudSign->getPosition()->getWorld()->getFolderName()
        ]);
        $cfg->save();

        $this->signs[$positionString] = $cloudSign;
    }

    public function removeCloudSign(CloudSign $cloudSign): void {
        if (!self::isEnabled()) return;
        ($ev = new CloudSignRemoveEvent($cloudSign))->call();
        if ($ev->isCancelled()) return;

        $positionString = Utils::convertToString($cloudSign->getPosition());
        $cfg = $this->getCloudSignConfig();
        $cfg->remove($positionString);
        $cfg->save();

        if (isset($this->signs[$positionString])) unset($this->signs[$positionString]);
        if ($cloudSign->hasUsingServer()) $this->removeUsingServerName($cloudSign->getUsingServer()->getName());
    }

    public function addUsingServerName(string $name, CloudSign $cloudSign): bool {
        if (!self::isEnabled()) return false;
        if (isset($this->usingServerNames[$name])) return false;
        $this->usingServerNames[$name] = $cloudSign;
        return true;
    }

    public function removeUsingServerName(string $name): bool {
        if (!self::isEnabled()) return false;
        if (!isset($this->usingServerNames[$name])) return false;
        unset($this->usingServerNames[$name]);
        return true;
    }

    public function getCloudSign(Position $position): ?CloudSign {
        return $this->signs[Utils::convertToString($position)] ?? null;
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

    public static function enable(): void {
        if (self::isEnabled()) return;
        self::setEnabled(true);
        self::getInstance()->load();
    }

    public static function disable(): void {
        if (!self::isEnabled()) return;
        self::setEnabled(false);
        self::getInstance()->unload();
    }
}