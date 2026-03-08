<?php

namespace pocketcloud\cloud\bridge\module\impl\npc\form\sub\model;

use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\module\impl\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\module\impl\npc\skin\CustomSkinModel;
use pocketmine\player\Player;
use r3pt1s\forms\element\custom\Input;
use r3pt1s\forms\type\custom\CustomForm;
use r3pt1s\forms\type\misc\CustomFormResponse;

final class SkinModelCreateForm extends CustomForm {

    public function __construct() {
        parent::__construct(
            LanguageKey::INGAME_UI_SKIN_MODEL_CREATE_TITLE(),
            [
                new Input("id", LanguageKey::INGAME_UI_SKIN_MODEL_CREATE_ELEMENT_ID_TEXT(), "bedwars.model"),
                new Input("skinImageFile", LanguageKey::INGAME_UI_SKIN_MODEL_CREATE_ELEMENT_SKIN_FILE_TEXT(), "./models/bedwars_skin.png"),
                new Input("geometryName", LanguageKey::INGAME_UI_SKIN_MODEL_CREATE_ELEMENT_GEO_NAME_TEXT(), "geometry.bedwars"),
                new Input("geometryDataFile", LanguageKey::INGAME_UI_SKIN_MODEL_CREATE_ELEMENT_GEO_FILE_TEXT(), "./models/bedwars_skin_geo.json")
            ]
        );
    }

    public function onSubmit(Player $player, CustomFormResponse $response): void {
        $id = $response->getString("id");
        $data = [
            "id" => $id,
            "skinImageFile" => $response->getString("skinImageFile"),
            "geometryName" => $response->getString("geometryName"),
            "geometryDataFile" => $response->getString("geometryDataFile")
        ];

        if (CloudNPCModule::get()->getSkinModel($id) !== null) {
            $player->sendMessage(LanguageKey::INGAME_SKIN_MODEL_EXISTS()->translate([$id]));
            return;
        }

        if (($model = CustomSkinModel::read($data)) !== null) {
            if (CloudNPCModule::get()->addSkinModel($model)) {
                $player->sendMessage(LanguageKey::INGAME_SKIN_MODEL_CREATED()->translate([$id]));
            } else {
                $player->sendMessage(LanguageKey::INGAME_PREFIX() . "§cAn error occurred while creating the model: §e" . $id);
            }
        } else {
            $player->sendMessage(LanguageKey::INGAME_SKIN_MODEL_FAILED()->translate([$id]));
        }
    }
}