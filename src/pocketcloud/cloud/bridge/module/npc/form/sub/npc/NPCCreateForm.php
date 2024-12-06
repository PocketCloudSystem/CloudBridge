<?php

namespace pocketcloud\cloud\bridge\module\npc\form\sub\npc;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use dktapps\pmforms\element\Toggle;
use pocketcloud\cloud\bridge\api\CloudAPI;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\language\Language;
use pocketcloud\cloud\bridge\module\npc\CloudNPC;
use pocketcloud\cloud\bridge\module\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\module\npc\group\TemplateGroup;
use pocketcloud\cloud\bridge\module\npc\skin\CustomSkinModel;
use pocketcloud\cloud\bridge\util\SkinSaver;
use pocketmine\player\Player;

class NPCCreateForm extends CustomForm {

    public function __construct() {
        parent::__construct(
            Language::current()->translate("inGame.ui.cloudnpc.create.title"),
            [new Dropdown(
                "name",
                Language::current()->translate("inGame.ui.cloudnpc.create.element.name.text"),
                $options = array_values(array_merge(
                    array_map(fn(Template $template) => $template->getName(), CloudAPI::templates()->getAll()),
                    array_map(fn(TemplateGroup $group) => $group->getDisplayName(), CloudNPCModule::get()->getTemplateGroups())
                ))
            ), new Dropdown(
                "model",
                Language::current()->translate("inGame.ui.cloudnpc.create.element.model.text"),
                $modelOptions = array_merge(["NONE"], array_values(array_map(fn(CustomSkinModel $model) => $model->getId(), CloudNPCModule::get()->getSkinModels())))
            ), new Toggle(
                "headRotation",
                Language::current()->translate("inGame.ui.cloudnpc.create.element.headRotation.text"),
                true
            )],
            function(Player $player, CustomFormResponse $response) use($options, $modelOptions): void {
                $template = CloudAPI::templates()->get($options[$response->getInt("name")]) ?? CloudNPCModule::get()->geTemplateGroupByDisplay($options[$response->getInt("name")]);
                if ($template !== null) {
                    $model = $modelOptions[$response->getInt("model")] == "NONE" ? null : CloudNPCModule::get()->getSkinModel($modelOptions[$response->getInt("model")]);
                    if (!CloudNPCModule::get()->checkCloudNPC($player->getPosition())) {
                        SkinSaver::save($player);
                        if (CloudNPCModule::get()->addCloudNPC(new CloudNPC(
                            $template,
                            $player->getLocation(),
                            $player->getName(),
                            $model,
                            $response->getBool("headRotation")
                        ))) {
                            $player->sendMessage(Language::current()->translate("inGame.cloudnpc.created"));
                        } else $player->sendMessage(CloudBridge::getPrefix() . "§cAn error occurred while creating the npc. Please report that incident on our discord.");
                    }
                } else $player->sendMessage(Language::current()->translate("inGame.template.not.found"));
            }
        );
    }
}