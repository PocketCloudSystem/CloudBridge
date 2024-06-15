<?php

namespace pocketcloud\cloudbridge\module\npc\form\sub\npc;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\object\template\Template;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\module\npc\CloudNPC;
use pocketcloud\cloudbridge\module\npc\CloudNPCModule;
use pocketcloud\cloudbridge\module\npc\group\TemplateGroup;
use pocketcloud\cloudbridge\module\npc\skin\CustomSkinModel;
use pocketcloud\cloudbridge\util\SkinSaver;
use pocketmine\player\Player;

class NPCCreateForm extends CustomForm {

    public function __construct() {
        parent::__construct(
            Language::current()->translate("inGame.ui.cloudnpc.create.title"),
            [new Dropdown(
                "name",
                Language::current()->translate("inGame.ui.cloudnpc.create.element.name.text"),
                $options = array_values(array_merge(
                    array_map(fn(Template $template) => $template->getName(), CloudAPI::templateProvider()->getTemplates()),
                    array_map(fn(TemplateGroup $group) => $group->getDisplayName(), CloudNPCModule::get()->getTemplateGroups())
                ))
            ), new Dropdown(
                "model",
                Language::current()->translate("inGame.ui.cloudnpc.create.element.model.text"),
                $modelOptions = array_merge(["NONE"], array_values(array_map(fn(CustomSkinModel $model) => $model->getId(), CloudNPCModule::get()->getSkinModels())))
            )],
            function(Player $player, CustomFormResponse $response) use($options, $modelOptions): void {
                $template = CloudAPI::templateProvider()->getTemplate($options[$response->getInt("name")]) ?? CloudNPCModule::get()->geTemplateGroupByDisplay($options[$response->getInt("name")]);
                if ($template !== null) {
                    $model = $modelOptions[$response->getInt("model")] == "NONE" ? null : CloudNPCModule::get()->getSkinModel($modelOptions[$response->getInt("model")]);
                    if (!CloudNPCModule::get()->checkCloudNPC($player->getPosition())) {
                        SkinSaver::save($player);
                        if (CloudNPCModule::get()->addCloudNPC(new CloudNPC(
                            $template,
                            $player->getPosition(),
                            $player->getName(),
                            $model
                        ))) {
                            $player->sendMessage(Language::current()->translate("inGame.cloudnpc.created"));
                        } else $player->sendMessage(CloudBridge::getPrefix() . "§cAn error occurred while creating the npc. Please report that incident on our discord.");
                    }
                } else $player->sendMessage(Language::current()->translate("inGame.template.not.found"));
            }
        );
    }
}