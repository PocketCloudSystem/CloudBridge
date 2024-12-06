<?php

namespace pocketcloud\cloud\bridge\module\npc\form;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\language\Language;
use pocketcloud\cloud\bridge\module\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\module\npc\form\sub\model\SkinModelCreateForm;
use pocketcloud\cloud\bridge\module\npc\form\sub\model\SkinModelEditForm;
use pocketcloud\cloud\bridge\module\npc\form\sub\model\SkinModelRemoveForm;
use pocketcloud\cloud\bridge\module\npc\skin\CustomSkinModel;
use pocketmine\player\Player;

class SkinModelMainForm extends MenuForm {

    public function __construct() {
        parent::__construct(
            Language::current()->translate("inGame.ui.skin_model.main.title"),
            Language::current()->translate("inGame.ui.skin_model.main.text"),
            [
                new MenuOption(Language::current()->translate("inGame.ui.skin_model.main.button.create")),
                new MenuOption(Language::current()->translate("inGame.ui.skin_model.main.button.edit")),
                new MenuOption(Language::current()->translate("inGame.ui.skin_model.main.button.remove")),
                new MenuOption(Language::current()->translate("inGame.ui.skin_model.main.button.list"))
            ],
            function(Player $player, int $data): void {
                if ($data == 0) {
                    $player->sendForm(new SkinModelCreateForm());
                } else if ($data == 1) {
                    if (empty(CloudNPCModule::get()->getSkinModels())) $player->sendMessage(CloudBridge::getPrefix() . "§7No models available.");
                    else $player->sendForm(new MenuForm(
                        Language::current()->translate("inGame.ui.skin_model.edit_selection.title"),
                        Language::current()->translate("inGame.ui.skin_model.edit_selection.text"),
                        array_map(fn(CustomSkinModel $model) => new MenuOption("§e" . $model->getId()), $models = array_values(CloudNPCModule::get()->getSkinModels())),
                        function (Player $player, int $data) use($models): void {
                            $model = $models[$data] ?? null;
                            if ($model !== null) {
                                $player->sendForm(new SkinModelEditForm($model));
                            }
                        }
                    ));
                } else if ($data == 2) {
                    if (empty(CloudNPCModule::get()->getSkinModels())) $player->sendMessage(CloudBridge::getPrefix() . "§7No models available.");
                    else $player->sendForm(new SkinModelRemoveForm());
                } else if ($data == 3) {
                    $player->sendMessage(CloudBridge::getPrefix() . "§7Models: §8(§e" . count(CloudNPCModule::get()->getSkinModels()) . "§8)§7:");
                    if (empty(CloudNPCModule::get()->getSkinModels())) $player->sendMessage(CloudBridge::getPrefix() . "§7No models available.");
                    foreach (CloudNPCModule::get()->getSkinModels() as $model) {
                        $player->sendMessage(
                            CloudBridge::getPrefix() . "§e" . $model->getId() .
                            " §8- §7Image File: §e" . $model->getSkinImageFile() .
                            " §8- §7Geometry Name: §e" . $model->getGeometryName() .
                            " §8- §7Geometry Data File: §e" . $model->getSkinImageFile()
                        );
                    }
                }
            }
        );
    }
}