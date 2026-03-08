<?php

namespace pocketcloud\cloud\bridge\module\impl\npc\form\sub\model;

use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\module\impl\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\module\impl\npc\skin\CustomSkinModel;
use pocketmine\player\Player;
use r3pt1s\forms\element\custom\Input;
use r3pt1s\forms\type\custom\CustomForm;
use r3pt1s\forms\type\misc\CustomFormResponse;

final class SkinModelEditForm extends CustomForm {

    public function __construct(private readonly CustomSkinModel $model) {
        parent::__construct(
            "§e" . $model->getId(),
            [
                new Input("skinImageFile", LanguageKey::INGAME_UI_SKIN_MODEL_EDIT_ELEMENT_SKIN_FILE_TEXT(), "./models/bedwars_skin.png", $model->getSkinImageFile()),
                new Input("geometryName", LanguageKey::INGAME_UI_SKIN_MODEL_EDIT_ELEMENT_GEO_NAME_TEXT(), "geometry.bedwars", $model->getGeometryName()),
                new Input("geometryDataFile", LanguageKey::INGAME_UI_SKIN_MODEL_EDIT_ELEMENT_GEO_FILE_TEXT(), "./models/bedwars_skin_geo.json", $model->getGeometryDataFile())
            ]
        );
    }

    public function onSubmit(Player $player, CustomFormResponse $response): void {
        $id = $this->model->getId();
        $data = [
            "id" => $id,
            "skinImageFile" => $response->getString("skinImageFile"),
            "geometryName" => $response->getString("geometryName"),
            "geometryDataFile" => $response->getString("geometryDataFile")
        ];

        if (($updated = CustomSkinModel::read($data)) !== null) {
            if (CloudNPCModule::get()->editSkinModel($updated)) {
                $player->sendMessage(LanguageKey::INGAME_SKIN_MODEL_EDITED()->translate([$id]));
            } else {
                $player->sendMessage(LanguageKey::INGAME_PREFIX() . "§cAn error occurred while editing the model: §e" . $id);
            }
        } else {
            $player->sendMessage(LanguageKey::INGAME_SKIN_MODEL_FAILED()->translate([$id]));
        }
    }
}