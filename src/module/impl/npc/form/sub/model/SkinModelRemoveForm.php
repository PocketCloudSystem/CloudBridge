<?php

namespace pocketcloud\cloud\bridge\module\impl\npc\form\sub\model;

use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\module\impl\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\module\impl\npc\skin\CustomSkinModel;
use pocketmine\player\Player;
use r3pt1s\forms\element\menu\MenuOption;
use r3pt1s\forms\type\menu\MenuForm;

final class SkinModelRemoveForm extends MenuForm {

    /** @var CustomSkinModel[] */
    private array $models;

    public function __construct() {
        $this->models = array_values(CloudNPCModule::get()->getSkinModels());
        parent::__construct(
            LanguageKey::INGAME_UI_SKIN_MODEL_REMOVE_TITLE(),
            LanguageKey::INGAME_UI_SKIN_MODEL_REMOVE_TEXT(),
            array_map(fn(CustomSkinModel $m) => new MenuOption("§e" . $m->getId()), $this->models)
        );
    }

    public function onSubmit(Player $player, int $index, MenuOption $option): void {
        $model = $this->models[$index] ?? null;
        if ($model === null) return;

        if (CloudNPCModule::get()->removeSkinModel($model)) {
            $player->sendMessage(LanguageKey::INGAME_SKIN_MODEL_REMOVED()->translate([$model->getId()]));
        } else {
            $player->sendMessage(LanguageKey::INGAME_PREFIX() . "§cAn error occurred while removing the model: §e" . $model->getId());
        }
    }
}