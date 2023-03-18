<?php

namespace pocketcloud\cloudbridge\module\sign;

use pocketcloud\cloudbridge\api\server\CloudServer;
use pocketcloud\cloudbridge\api\server\status\ServerStatus;
use pocketcloud\cloudbridge\api\template\Template;
use pocketcloud\cloudbridge\config\SignLayoutConfig;
use pocketmine\world\Position;

class CloudSign {

    private ?CloudServer $usingServer = null;
    private int $stateIndex = -1;
    private int $layerIndex = 0;

    public function __construct(private Template $template, private Position $position) {}

    public function next(): array {
        $useDefault = false;
        $this->layerIndex++;

        if ($this->hasUsingServer()) {
            if ($this->getUsingServer()->getTemplate()?->isMaintenance()) {
                if ($this->stateIndex !== 3) $this->stateIndex = 3;
            } else if ($this->getUsingServer()->getServerStatus() === ServerStatus::ONLINE()) {
                if ($this->stateIndex !== 0) $this->stateIndex = 0;
            } else if ($this->getUsingServer()->getServerStatus() === ServerStatus::FULL()) {
                if ($this->stateIndex !== 1) $this->stateIndex = 1;
            } else if ($this->getUsingServer()->getServerStatus() === ServerStatus::IN_GAME()) {
                if ($this->stateIndex !== 2) $this->stateIndex = 2;
            } else {
                if ($this->stateIndex !== 2) $this->stateIndex = 2;
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
            if ($this->stateIndex !== 2) $this->stateIndex = 2;

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

    public function setUsingServer(?CloudServer $usingServer): void {
        $this->usingServer = $usingServer;
    }

    public function getTemplate(): Template {
        return $this->template;
    }

    public function getPosition(): Position {
        return $this->position;
    }

    public function getUsingServer(): ?CloudServer {
        return $this->usingServer;
    }

    public function hasUsingServer(): bool {
        return $this->usingServer !== null;
    }
}