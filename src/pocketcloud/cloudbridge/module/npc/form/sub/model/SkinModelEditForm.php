<?php

namespace pocketcloud\cloudbridge\module\npc\form\sub\model;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\module\npc\CloudNPCModule;
use pocketcloud\cloudbridge\module\npc\skin\CustomSkinModel;
use pocketmine\player\Player;

class SkinModelEditForm extends CustomForm {

    public function __construct(CustomSkinModel $model) {
        parent::__construct(
            "§e" . $model->getId(),
            [
                new Input("skinImageFile", Language::current()->translate("inGame.ui.skin_model.edit.element.skin_file.text"), "./models/bedwars_skin.png", $model->getSkinImageFile()),
                new Input("geometryName", Language::current()->translate("inGame.ui.skin_model.edit.element.geo_name.text"), "geometry.bedwars", $model->getGeometryName()),
                new Input("geometryDataFile", Language::current()->translate("inGame.ui.skin_model.edit.element.geo_file.text"), "./models/bedwars_skin_geo.json", $model->getGeometryDataFile())
            ],
            function (Player $player, CustomFormResponse $response) use($model): void {
                $data = [
                    "id" => ($id = $model->getId()),
                    "skinImageFile" => $response->getString("skinImageFile"),
                    "geometryName" => $response->getString("geometryName"),
                    "geometryDataFile" => $response->getString("geometryDataFile")
                ];

                if (($model = CustomSkinModel::fromArray($data)) !== null) {
                    if (CloudNPCModule::get()->editSkinModel($model)) {
                        $player->sendMessage(Language::current()->translate("inGame.skin_model.edited", $id));
                    } else $player->sendMessage(CloudBridge::getPrefix() . "§cAn error occurred while editing the model: §e" . $id . "§c. Please report that incident on our discord.");
                } else $player->sendMessage(Language::current()->translate("inGame.skin_model.failed", $id));
            }
        );
    }
}