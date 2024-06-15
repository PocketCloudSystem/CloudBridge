<?php

namespace pocketcloud\cloudbridge\module\sign;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\object\server\CloudServer;
use pocketcloud\cloudbridge\api\object\server\status\ServerStatus;
use pocketcloud\cloudbridge\api\object\template\Template;
use pocketcloud\cloudbridge\module\sign\config\SignLayoutConfig;
use pocketcloud\cloudbridge\util\Utils;
use pocketmine\world\Position;

class CloudSign {

    private ?string $usingServer = null;
    private int $layerIndex = 0;

    public function __construct(
        private readonly Template $template,
        private readonly Position $position
    ) {}

    public function next(): array {
        $this->layerIndex++;

        if ($this->hasUsingServer()) {
            if ($this->getUsingServer()->getTemplate()?->isMaintenance()) {
                $stateIndex = 3;
            } else if ($this->getUsingServer()->getServerStatus() === ServerStatus::ONLINE()) {
               $stateIndex = 0;
            } else if ($this->getUsingServer()->getServerStatus() === ServerStatus::FULL()) {
                $stateIndex = 1;
            } else {
                $stateIndex = 2;
            }

            if (!$this->checkIndexes($stateIndex, $this->layerIndex)) {
                if (!isset(SignLayoutConfig::DEFAULT_LAYOUTS[$stateIndex][$this->layerIndex])) $this->layerIndex = 0;

                $layers = [];
                foreach (SignLayoutConfig::DEFAULT_LAYOUTS[$stateIndex][$this->layerIndex] ?? [] as $layer) {
                    $layers[] = str_replace(["%template%", "%server%", "%players%", "%max_players%"], [$this->template->getName(), $this->getUsingServer()->getName(), count($this->getUsingServer()->getCloudPlayers()), $this->getUsingServer()->getCloudServerData()->getMaxPlayers()], $layer);
                }

                return $layers;
            }

            if (!isset(SignLayoutConfig::getInstance()->getConfig()->getAll()[$stateIndex][$this->layerIndex])) $this->layerIndex = 0;

            $layers = [];
            foreach (SignLayoutConfig::getInstance()->getConfig()->getAll()[$stateIndex][$this->layerIndex] ?? [] as $layer) {
                $layers[] = str_replace(["%template%", "%server%", "%players%", "%max_players%"], [$this->template->getName(), $this->getUsingServer()->getName(), count($this->getUsingServer()->getCloudPlayers()), $this->getUsingServer()->getCloudServerData()->getMaxPlayers()], $layer);
            }

        } else {
            $stateIndex = 2;

            if (!$this->checkIndexes($stateIndex, $this->layerIndex)) {
                if (!isset(SignLayoutConfig::DEFAULT_LAYOUTS[$stateIndex][$this->layerIndex])) $this->layerIndex = 0;

                $layers = [];
                foreach (SignLayoutConfig::DEFAULT_LAYOUTS[$stateIndex][$this->layerIndex] ?? [] as $layer) {
                    $layers[] = str_replace(["%template%"], [$this->template->getName()], $layer);
                }

                return $layers;
            }

            if (!isset(SignLayoutConfig::getInstance()->getConfig()->getAll()[$stateIndex][$this->layerIndex])) $this->layerIndex = 0;

            $layers = [];
            foreach (SignLayoutConfig::getInstance()->getConfig()->getAll()[$stateIndex][$this->layerIndex] ?? [] as $layer) {
                $layers[] = str_replace(["%template%"], [$this->template->getName()], $layer);
            }

        }
        return $layers;
    }

    private function checkIndexes(int $stateIndex, int $layerIndex = -1): bool {
        if (!isset(SignLayoutConfig::getInstance()->getConfig()->getAll()[$stateIndex])) {
            return false;
        } else {
            if (!isset(SignLayoutConfig::getInstance()->getConfig()->getAll()[$stateIndex][$layerIndex])) {
                if (!isset(SignLayoutConfig::getInstance()->getConfig()->getAll()[$stateIndex][($layerIndex - 1)])) return false;
            }
        }

        return true;
    }

    public function setUsingServer(?string $usingServer): void {
        $this->usingServer = $usingServer;
    }

    public function getTemplate(): Template {
        return $this->template;
    }

    public function getPosition(): Position {
        return $this->position;
    }

    public function getUsingServer(): ?CloudServer {
        return $this->usingServer === null ? null : CloudAPI::serverProvider()->getServer($this->usingServer);
    }

    public function getUsingServerName(): ?string {
        return $this->usingServer;
    }

    public function hasUsingServer(): bool {
        return $this->getUsingServer() !== null;
    }

    public function toArray(): array {
        return [
            "template" => $this->template->getName(),
            "position" => Utils::convertToString($this->position)
        ];
    }

    public static function fromArray(array $data): ?CloudSign {
        if (!Utils::containKeys($data, "template", "position")) return null;
        /** @var Position $position */
        $position = Utils::convertToVector($data["position"]);
        if (($template = CloudAPI::templateProvider()->getTemplate($data["template"])) !== null && $position instanceof Position) {
            return new CloudSign($template, $position);
        }
        return null;
    }
}