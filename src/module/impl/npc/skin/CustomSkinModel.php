<?php

namespace pocketcloud\cloud\bridge\module\impl\npc\skin;

use pocketcloud\cloud\bridge\module\impl\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\util\misc\Writeable;
use pocketcloud\cloud\bridge\util\Utils;
use pocketmine\entity\Skin;
use Throwable;

final readonly class CustomSkinModel implements Writeable {

    public function __construct(
        private string $id,
        private string $skinImageFile,
        private string $geometryName,
        private string $geometryDataFile,
    ) {}

    public function createSkin(): ?Skin {
        $skinImageFile = self::resolvePath($this->skinImageFile);
        $geometryDataFile = self::resolvePath($this->geometryDataFile);
        if (!@file_exists($skinImageFile) || !@file_exists($geometryDataFile)) return null;
        try {
            $skinData = Utils::fromImage($skinImageFile);
            return new Skin("Standard_Custom", $skinData, "", $this->geometryName, file_get_contents($geometryDataFile));
        } catch (Throwable $exception) {
            CloudNPCModule::get()->getLogger()->logException($exception);
        }

        return null;
    }

    public static function resolvePath(string $file): string {
        if (str_starts_with($file, "./")) { // ./ = cloudbridge plugin data folder
            return str_replace("./", CloudNPCModule::get()->getPlugin()->getDataFolder(), $file);
        }

        return $file;
    }

    public function getId(): string {
        return $this->id;
    }

    public function getSkinImageFile(): string {
        return $this->skinImageFile;
    }

    public function getGeometryName(): string {
        return $this->geometryName;
    }

    public function getGeometryDataFile(): string {
        return $this->geometryDataFile;
    }

    public function write(): array {
        return [
            "id" => $this->id,
            "skinImageFile" => $this->skinImageFile,
            "geometryName" => $this->geometryName,
            "geometryDataFile" => $this->geometryDataFile
        ];
    }

    public static function read(array $data): ?self {
        if (!Utils::containKeys($data, "id", "skinImageFile", "geometryName", "geometryDataFile")) return null;
        if (!@file_exists(self::resolvePath($data["skinImageFile"])) || !@file_exists(self::resolvePath($data["geometryDataFile"]))) return null;
        return new self(...$data);
    }
}