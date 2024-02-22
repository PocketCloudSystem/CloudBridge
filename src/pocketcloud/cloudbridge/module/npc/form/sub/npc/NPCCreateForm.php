<?php

namespace pocketcloud\cloudbridge\module\npc\form\sub\npc;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\template\Template;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\module\npc\CloudNPC;
use pocketcloud\cloudbridge\module\npc\CloudNPCModule;
use pocketcloud\cloudbridge\module\npc\group\TemplateGroup;
use pocketcloud\cloudbridge\util\SkinSaver;
use pocketmine\player\Player;

class NPCCreateForm extends CustomForm {

    public function __construct() {
        parent::__construct(
            Language::current()->translate("inGame.ui.cloudnpc.create.title"),
            [new Dropdown(
                "name",
                Language::current()->translate("inGame.ui.cloudnpc.create.element.name.text"),
                $options = array_merge(
                    array_map(fn(Template $template) => $template->getName(), CloudAPI::getInstance()->getTemplates()),
                    array_map(fn(TemplateGroup $group) => $group->getDisplayName(), CloudNPCModule::get()->getTemplateGroups())
                )
            )],
            function(Player $player, CustomFormResponse $response) use($options): void {
                $template = CloudAPI::getInstance()->getTemplateByName($options[$response->getInt("name")]) ?? CloudNPCModule::get()->geTemplateGroupByDisplay($options[$response->getInt("name")]);
                if ($template !== null) {
                    if (!CloudNPCModule::get()->checkCloudNPC($player->getPosition())) {
                        SkinSaver::save($player);
                        if (CloudNPCModule::get()->addCloudNPC(new CloudNPC(
                            $template,
                            $player->getPosition(),
                            $player->getName()
                        ))) {
                            $player->sendMessage(Language::current()->translate("inGame.cloudnpc.created"));
                        } else $player->sendMessage(CloudBridge::getPrefix() . "§cAn error occurred while creating the npc. Please report that incident on our discord.");
                    }
                } else $player->sendMessage(Language::current()->translate("inGame.template.not.found"));
            }
        );
    }
}