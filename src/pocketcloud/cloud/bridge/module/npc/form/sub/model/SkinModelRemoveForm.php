<?php

namespace pocketcloud\cloud\bridge\module\npc\form\sub\model;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\language\Language;
use pocketcloud\cloud\bridge\module\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\module\npc\skin\CustomSkinModel;
use pocketmine\player\Player;

final class SkinModelRemoveForm extends MenuForm {

    public function __construct() {
        parent::__construct(
            Language::current()->translate("inGame.ui.skin_model.remove.title"),
            Language::current()->translate("inGame.ui.skin_model.remove.text"),
            array_map(fn(CustomSkinModel $model) => new MenuOption("§e" . $model->getId()), $models = array_values(CloudNPCModule::get()->getSkinModels())),
            function (Player $player, int $data) use($models): void {
                $model = $models[$data] ?? null;
                if ($model !== null) {
                    if (CloudNPCModule::get()->removeSkinModel($model)) {
                        $player->sendMessage(Language::current()->translate("inGame.skin_model.removed", $model->getId()));
                    } else $player->sendMessage(CloudBridge::getPrefix() . "§cAn error occurred while removing the model: §e" . $model->getId() . "§c. Please report that incident on our discord.");
                }
            }
        );
    }
}