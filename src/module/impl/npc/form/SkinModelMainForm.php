<?php

namespace pocketcloud\cloud\bridge\module\impl\npc\form;

use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\module\impl\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\module\impl\npc\form\sub\model\SkinModelCreateForm;
use pocketcloud\cloud\bridge\module\impl\npc\form\sub\model\SkinModelEditForm;
use pocketcloud\cloud\bridge\module\impl\npc\form\sub\model\SkinModelRemoveForm;
use pocketcloud\cloud\bridge\module\impl\npc\skin\CustomSkinModel;
use pocketmine\player\Player;
use r3pt1s\forms\builder\MenuFormBuilder;
use r3pt1s\forms\element\menu\MenuOption;
use r3pt1s\forms\type\menu\MenuForm;

final class SkinModelMainForm extends MenuForm {

    public function __construct() {
        parent::__construct(
            LanguageKey::INGAME_UI_SKIN_MODEL_MAIN_TITLE(),
            LanguageKey::INGAME_UI_SKIN_MODEL_MAIN_TEXT(),
            [
                new MenuOption(LanguageKey::INGAME_UI_SKIN_MODEL_MAIN_BUTTON_CREATE()),
                new MenuOption(LanguageKey::INGAME_UI_SKIN_MODEL_MAIN_BUTTON_EDIT()),
                new MenuOption(LanguageKey::INGAME_UI_SKIN_MODEL_MAIN_BUTTON_REMOVE()),
                new MenuOption(LanguageKey::INGAME_UI_SKIN_MODEL_MAIN_BUTTON_LIST())
            ]
        );
    }

    public function onSubmit(Player $player, int $index, MenuOption $option): void {
        if ($index === 0) {
            $player->sendForm(new SkinModelCreateForm());
        } elseif ($index === 1) {
            $models = array_values(CloudNPCModule::get()->getSkinModels());
            if (empty($models)) {
                $player->sendMessage(LanguageKey::INGAME_PREFIX() . "§7No models available.");
                return;
            }

            $player->sendForm(MenuFormBuilder::create(LanguageKey::INGAME_UI_SKIN_MODEL_EDIT_SELECTION_TITLE(), LanguageKey::INGAME_UI_SKIN_MODEL_EDIT_SELECTION_TEXT())
                ->elements(array_map(fn(CustomSkinModel $m) => new MenuOption("§e" . $m->getId()), $models))
                ->onSubmit(function (Player $player, int $index) use ($models): void {
                    $model = $models[$index] ?? null;
                    if ($model !== null) {
                        $player->sendForm(new SkinModelEditForm($model));
                    }
                })
                ->build()
            );
        } elseif ($index === 2) {
            if (empty(CloudNPCModule::get()->getSkinModels())) {
                $player->sendMessage(LanguageKey::INGAME_PREFIX() . "§7No models available.");
                return;
            }
            $player->sendForm(new SkinModelRemoveForm());
        } elseif ($index === 3) {
            $models = CloudNPCModule::get()->getSkinModels();
            $player->sendMessage(LanguageKey::INGAME_PREFIX() . "§7Models: §8(§e" . count($models) . "§8)§7:");
            if (empty($models)) {
                $player->sendMessage(LanguageKey::INGAME_PREFIX() . "§7No models available.");
                return;
            }

            foreach ($models as $model) {
                $player->sendMessage(
                    LanguageKey::INGAME_PREFIX() . "§e" . $model->getId() .
                    " §8- §7Image: §e" . $model->getSkinImageFile() .
                    " §8- §7Geo Name: §e" . $model->getGeometryName() .
                    " §8- §7Geo File: §e" . $model->getGeometryDataFile()
                );
            }
        }
    }
}