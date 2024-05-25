<?php

namespace pocketcloud\cloudbridge\module\sign;

use JsonException;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\event\sign\CloudSignAddEvent;
use pocketcloud\cloudbridge\event\sign\CloudSignRemoveEvent;
use pocketcloud\cloudbridge\module\BaseModule;
use pocketcloud\cloudbridge\module\sign\listener\SignListener;
use pocketcloud\cloudbridge\module\sign\task\CloudSignTask;
use pocketcloud\cloudbridge\util\Utils;
use pocketmine\event\HandlerListManager;
use pocketmine\utils\Config;
use pocketmine\world\Position;

final class CloudSignModule extends BaseModule {

    public array $signDelay = [];
    /** @var array<CloudSign> */
    private array $signs = [];
    /** @var array<CloudSign> */
    private array $usingServerNames = [];
    private ?SignListener $listener = null;
    private ?CloudSignTask $task = null;

    protected function onEnable(): void {
        $this->listener = new SignListener();
        $this->task = new CloudSignTask();
        $this->load();
    }

    protected function onDisable(): void {
        $this->task?->getHandler()->cancel();
        if ($this->listener !== null) HandlerListManager::global()->unregisterAll($this->listener);
        $this->signDelay = [];
        $this->signs = [];
        $this->usingServerNames = [];
        $this->listener = null;
    }

    private function load(): void {
        foreach ($this->getCloudSignConfig()->getAll() as $positionString => $cloudSign) {
            if (($cloudSign = CloudSign::fromArray($this->checkForMigration($cloudSign))) !== null) {
                $this->signs[$positionString] = $cloudSign;
            }
        }

        CloudBridge::getInstance()->getScheduler()->scheduleRepeatingTask($this->task, 20);
    }

    private function checkForMigration(array $data): array {
        foreach (["Template" => "template", "Position" => "position", "World" => null] as $key => $newKey) {
            if (isset($data[$key])) {
                if ($newKey !== null) $data[$newKey] = $data[$key];
                unset($data[$key]);
            }
        }

        return $data;
    }

    public function addCloudSign(CloudSign $cloudSign): bool {
        $positionString = Utils::convertToString($cloudSign->getPosition());
        if (isset($this->signs[$positionString])) return false;
        ($ev = new CloudSignAddEvent($cloudSign))->call();
        if ($ev->isCancelled()) return false;

        try {
            ($cfg = $this->getCloudSignConfig())->set($positionString, $cloudSign->toArray());
            $cfg->save();
        } catch (JsonException $exception) {
            CloudSignModule::get()->getLogger()->logException($exception);
            return false;
        } finally {
            $this->signs[$positionString] = $cloudSign;
        }

        return true;
    }

    public function removeCloudSign(CloudSign $cloudSign): bool {
        $positionString = Utils::convertToString($cloudSign->getPosition());
        if (!isset($this->signs[$positionString])) return false;
        ($ev = new CloudSignRemoveEvent($cloudSign))->call();
        if ($ev->isCancelled()) return false;

        try {
            ($cfg = $this->getCloudSignConfig())->remove($positionString);
            $cfg->save();
        } catch (JsonException $exception) {
            CloudSignModule::get()->getLogger()->logException($exception);
            return false;
        } finally {
            if ($cloudSign->hasUsingServer()) $this->removeUsingServerName($cloudSign->getUsingServerName());
            unset($this->signs[$positionString]);
        }

        return true;
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
}