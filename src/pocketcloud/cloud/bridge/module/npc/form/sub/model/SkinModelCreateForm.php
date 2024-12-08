<?php

namespace pocketcloud\cloud\bridge\module\npc\form\sub\model;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\language\Language;
use pocketcloud\cloud\bridge\module\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\module\npc\skin\CustomSkinModel;
use pocketmine\player\Player;

final class SkinModelCreateForm extends CustomForm {

    public function __construct() {
        parent::__construct(
            Language::current()->translate("inGame.ui.skin_model.create.title"),
            [
                new Input("id", Language::current()->translate("inGame.ui.skin_model.create.element.id.text"), "bedwars.model"),
                new Input("skinImageFile", Language::current()->translate("inGame.ui.skin_model.create.element.skin_file.text"), "./models/bedwars_skin.png"),
                new Input("geometryName", Language::current()->translate("inGame.ui.skin_model.create.element.geo_name.text"), "geometry.bedwars"),
                new Input("geometryDataFile", Language::current()->translate("inGame.ui.skin_model.create.element.geo_file.text"), "./models/bedwars_skin_geo.json")
            ],
            function (Player $player, CustomFormResponse $response): void {
                $data = [
                    "id" => ($id = $response->getString("id")),
                    "skinImageFile" => $response->getString("skinImageFile"),
                    "geometryName" => $response->getString("geometryName"),
                    "geometryDataFile" => $response->getString("geometryDataFile")
                ];

                if (CloudNPCModule::get()->getSkinModel($id) === null) {
                    if (($model = CustomSkinModel::fromArray($data)) !== null) {
                        if (CloudNPCModule::get()->addSkinModel($model)) {
                            $player->sendMessage(Language::current()->translate("inGame.skin_model.created", $id));
                        } else $player->sendMessage(CloudBridge::getPrefix() . "§cAn error occurred while creating the model: §e" . $id . "§c. Please report that incident on our discord.");
                    } else $player->sendMessage(Language::current()->translate("inGame.skin_model.failed", $id));
                } else $player->sendMessage(Language::current()->translate("inGame.skin_model.exists", $id));
            }
        );
    }
}