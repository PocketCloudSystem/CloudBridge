<?php

namespace pocketcloud\cloudbridge\module\sign;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\server\CloudServer;
use pocketcloud\cloudbridge\api\server\status\ServerStatus;
use pocketcloud\cloudbridge\api\template\Template;
use pocketcloud\cloudbridge\module\sign\config\SignLayoutConfig;
use pocketmine\world\Position;

class CloudSign {

    private ?string $usingServer = null;
    private int $stateIndex = -1;
    private int $layerIndex = 0;

    public function __construct(
        private readonly Template $template,
        private readonly Position $position
    ) {}

    public function next(): array {
        $useDefault = false;
        $this->layerIndex++;

        if ($this->hasUsingServer()) {
            if ($this->getUsingServer()->getTemplate()?->isMaintenance()) {
                $this->stateIndex = 3;
            } else if ($this->getUsingServer()->getServerStatus() === ServerStatus::ONLINE()) {
               $this->stateIndex = 0;
            } else if ($this->getUsingServer()->getServerStatus() === ServerStatus::FULL()) {
                $this->stateIndex = 1;
            } else {
                $this->stateIndex = 2;
            }

            if (!isset(SignLayoutConfig::getInstance()->getConfig()->getAll()[$this->stateIndex])) {
                $useDefault = true;
            } else {
                if (!isset(SignLayoutConfig::getInstance()->getConfig()->getAll()[$this->stateIndex][$this->layerIndex])) {
                    if (!isset(SignLayoutConfig::getInstance()->getConfig()->getAll()[$this->stateIndex][($this->layerIndex - 1)])) $useDefault = true;
                }
            }

            if ($useDefault) {
                if (!isset(SignLayoutConfig::DEFAULT_LAYOUTS[$this->stateIndex][$this->layerIndex])) $this->layerIndex = 0;

                $layers = [];
                foreach (SignLayoutConfig::DEFAULT_LAYOUTS[$this->stateIndex][$this->layerIndex] ?? [] as $layer) {
                    $layers[] = str_replace(["%template%", "%server%", "%players%", "%max_players%"], [$this->template->getName(), $this->getUsingServer()->getName(), count($this->getUsingServer()->getCloudPlayers()), $this->getUsingServer()->getCloudServerData()->getMaxPlayers()], $layer);
                }

                return $layers;
            }

            if (!isset(SignLayoutConfig::getInstance()->getConfig()->getAll()[$this->stateIndex][$this->layerIndex])) $this->layerIndex = 0;

            $layers = [];
            foreach (SignLayoutConfig::getInstance()->getConfig()->getAll()[$this->stateIndex][$this->layerIndex] ?? [] as $layer) {
                $layers[] = str_replace(["%template%", "%server%", "%players%", "%max_players%"], [$this->template->getName(), $this->getUsingServer()->getName(), count($this->getUsingServer()->getCloudPlayers()), $this->getUsingServer()->getCloudServerData()->getMaxPlayers()], $layer);
            }

            return $layers;
        } else {
            $this->stateIndex = 2;

            if (!isset(SignLayoutConfig::getInstance()->getConfig()->getAll()[$this->stateIndex])) {
                $useDefault = true;
            } else {
                if (!isset(SignLayoutConfig::getInstance()->getConfig()->getAll()[$this->stateIndex][$this->layerIndex])) {
                    if (!isset(SignLayoutConfig::getInstance()->getConfig()->getAll()[$this->stateIndex][($this->layerIndex - 1)])) $useDefault = true;
                }
            }

            if ($useDefault) {
                if (!isset(SignLayoutConfig::DEFAULT_LAYOUTS[$this->stateIndex][$this->layerIndex])) $this->layerIndex = 0;

                $layers = [];
                foreach (SignLayoutConfig::DEFAULT_LAYOUTS[$this->stateIndex][$this->layerIndex] ?? [] as $layer) {
                    $layers[] = str_replace(["%template%"], [$this->template->getName()], $layer);
                }

                return $layers;
            }

            if (!isset(SignLayoutConfig::getInstance()->getConfig()->getAll()[$this->stateIndex][$this->layerIndex])) $this->layerIndex = 0;

            $layers = [];
            foreach (SignLayoutConfig::getInstance()->getConfig()->getAll()[$this->stateIndex][$this->layerIndex] ?? [] as $layer) {
                $layers[] = str_replace(["%template%"], [$this->template->getName()], $layer);
            }

            return $layers;
        }
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
        return $this->usingServer === null ? null : CloudAPI::getInstance()->getServerByName($this->usingServer);
    }

    public function getUsingServerName(): ?string {
        return $this->usingServer;
    }

    public function hasUsingServer(): bool {
        return $this->getUsingServer() !== null;
    }
}