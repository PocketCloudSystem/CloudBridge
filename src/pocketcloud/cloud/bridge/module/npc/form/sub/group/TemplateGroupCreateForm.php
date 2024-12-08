<?php

namespace pocketcloud\cloud\bridge\module\npc\form\sub\group;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\language\Language;
use pocketcloud\cloud\bridge\module\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\module\npc\group\TemplateGroup;
use pocketmine\player\Player;

final class TemplateGroupCreateForm extends CustomForm {

    public function __construct() {
        parent::__construct(
            Language::current()->translate("inGame.ui.template_group.create.title"),
            [
                new Input("id", Language::current()->translate("inGame.ui.template_group.create.element.id.text"), "bedwars.group"),
                new Input("display", Language::current()->translate("inGame.ui.template_group.create.element.display.text"), "§cBedWars")
            ],
            function (Player $player, CustomFormResponse $response): void {
                $id = $response->getString("id");
                $display = $response->getString("display");

                if (CloudNPCModule::get()->getTemplateGroup($id) === null) {
                    if (CloudNPCModule::get()->addTemplateGroup(new TemplateGroup($id, $display, []))) {
                        $player->sendMessage(Language::current()->translate("inGame.template_group.created", $id));
                    } else $player->sendMessage(CloudBridge::getPrefix() . "§cAn error occurred while creating the group: §e" . $id . "§c. Please report that incident on our discord.");
                } else $player->sendMessage(Language::current()->translate("inGame.template_group.exists", $id));
            }
        );
    }
}