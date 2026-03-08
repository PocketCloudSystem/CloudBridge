<?php

namespace pocketcloud\cloud\bridge\module\impl\sign;

use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\object\server\util\ServerStatus;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\module\impl\sign\config\SignLayoutConfig;
use pocketcloud\cloud\bridge\util\misc\Writeable;
use pocketcloud\cloud\bridge\util\Utils;
use pocketmine\block\BaseSign;
use pocketmine\block\utils\SignText;
use pocketmine\world\Position;

final class CloudSign implements Writeable {

    public const int INDEX_LOBBY = 0;
    public const int INDEX_FULL = 1;
    public const int INDEX_SEARCHING = 2;
    public const int INDEX_MAINTENANCE = 3;

    private ?string $usingServer = null;
    private int $layerIndex = 0;

    public function __construct(
        private readonly Template $template,
        private readonly Position $position
    ) {}

    public function reloadSign(BaseSign $signBlock): void {
        $signBlock->setFaceText(true, new SignText($this->next()));
        $signBlock->getPosition()->getWorld()->setBlock($signBlock->getPosition()->asVector3(), $signBlock);
    }

    public function next(): array {
        $this->layerIndex++;

        if ($this->hasUsingServer()) {
            if ($this->getUsingServer()->getTemplate()?->isMaintenance()) {
                $stateIndex = self::INDEX_MAINTENANCE;
            } else if ($this->getUsingServer()->getServerStatus() === ServerStatus::ONLINE) {
                $stateIndex = self::INDEX_LOBBY;
            } else if ($this->getUsingServer()->getServerStatus() === ServerStatus::FULL) {
                $stateIndex = self::INDEX_FULL;
            } else {
                $stateIndex = self::INDEX_SEARCHING;
            }

            if (!$this->checkIndexes($stateIndex, $this->layerIndex)) {
                if (!isset(SignLayoutConfig::DEFAULT_LAYOUTS[$stateIndex][$this->layerIndex])) $this->layerIndex = 0;

                $layers = [];
                foreach (SignLayoutConfig::DEFAULT_LAYOUTS[$stateIndex][$this->layerIndex] ?? [] as $layer) {
                    $layers[] = str_replace(["%template%", "%server%", "%players%", "%max_players%"], [$this->template->getName(), $this->getUsingServer()->getName(), $this->getUsingServer()->getPlayerCount(), $this->getUsingServer()->getTemplate()->getMaxPlayerCount()], $layer);
                }

                return $layers;
            }

            if (!isset(SignLayoutConfig::getInstance()->getConfig()->getAll()[$stateIndex][$this->layerIndex])) $this->layerIndex = 0;

            $layers = [];
            foreach (SignLayoutConfig::getInstance()->getConfig()->getAll()[$stateIndex][$this->layerIndex] ?? [] as $layer) {
                $layers[] = str_replace(["%template%", "%server%", "%players%", "%max_players%"], [$this->template->getName(), $this->getUsingServer()->getName(), $this->getUsingServer()?->getPlayerCount() ?? 0, $this->getUsingServer()->getTemplate()->getMaxPlayerCount()], $layer);
            }

        } else {
            $stateIndex = self::INDEX_SEARCHING;

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

    public function onSetServer(?string $server): void {
        if ($server !== null) CloudSignModule::get()->addUsingServerName($server, $this);
        if ($this->hasUsingServer()) CloudSignModule::get()->removeUsingServerName($this->usingServer);
        $this->setUsingServer($server);
    }

    public function hasUsingServer(): bool {
        return $this->getUsingServer() !== null;
    }

    public function getUsingServer(): ?CloudServer {
        return $this->usingServer === null ? null : CloudServerProvider::provider()->get($this->usingServer);
    }

    public function setUsingServer(?string $usingServer): void {
        $this->usingServer = $usingServer;
    }

    public function onRemoveServer(): void {
        CloudSignModule::get()->removeUsingServerName($this->getUsingServerName());
        $this->setUsingServer(null);
    }

    public function getUsingServerName(): ?string {
        return $this->usingServer;
    }

    public function getTemplate(): Template {
        return $this->template;
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

    public function getPosition(): Position {
        return $this->position;
    }

    public function isHoldingServer(): bool {
        return $this->usingServer !== null;
    }

    public function write(): array {
        return [
            "template" => $this->template->getName(),
            "position" => Utils::convertToString($this->position)
        ];
    }

    public static function read(array $data): ?CloudSign {
        if (!Utils::containKeys($data, "template", "position")) return null;
        /** @var Position $position */
        $position = Utils::convertToVector($data["position"]);
        if (($template = TemplateProvider::provider()->get($data["template"])) !== null && $position instanceof Position) return new CloudSign($template, $position);
        return null;
    }
}