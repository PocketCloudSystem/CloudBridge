<?php

namespace pocketcloud\cloud\bridge\module\impl\npc\form\sub\group;

use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\module\impl\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\module\impl\npc\group\TemplateGroup;
use pocketmine\player\Player;
use r3pt1s\forms\element\custom\Input;
use r3pt1s\forms\type\custom\CustomForm;
use r3pt1s\forms\type\misc\CustomFormResponse;

final class TemplateGroupCreateForm extends CustomForm {

    public function __construct() {
        parent::__construct(
            LanguageKey::INGAME_UI_TEMPLATE_GROUP_CREATE_TITLE(),
            [
                new Input("id", LanguageKey::INGAME_UI_TEMPLATE_GROUP_CREATE_ELEMENT_ID_TEXT(), "bedwars.group"),
                new Input("display", LanguageKey::INGAME_UI_TEMPLATE_GROUP_CREATE_ELEMENT_DISPLAY_TEXT(), "§cBedWars")
            ]
        );
    }

    public function onSubmit(Player $player, CustomFormResponse $response): void {
        $id = $response->getString("id");
        $display = $response->getString("display");

        if (CloudNPCModule::get()->getTemplateGroup($id) !== null) {
            $player->sendMessage(LanguageKey::INGAME_TEMPLATE_GROUP_EXISTS()->translate([$id]));
            return;
        }

        if (CloudNPCModule::get()->addTemplateGroup(new TemplateGroup($id, $display, []))) {
            $player->sendMessage(LanguageKey::INGAME_TEMPLATE_GROUP_CREATED()->translate([$id]));
        } else {
            $player->sendMessage(LanguageKey::INGAME_PREFIX() . "§cAn error occurred while creating the group: §e" . $id);
        }
    }
}