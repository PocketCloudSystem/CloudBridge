<?php

namespace pocketcloud\cloud\bridge\module\impl\npc\form\sub\npc;

use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\module\impl\npc\CloudNPC;
use pocketcloud\cloud\bridge\module\impl\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\module\impl\npc\group\TemplateGroup;
use pocketcloud\cloud\bridge\module\impl\npc\skin\CustomSkinModel;
use pocketcloud\cloud\bridge\util\SkinSaver;
use pocketmine\player\Player;
use r3pt1s\forms\element\custom\Dropdown;
use r3pt1s\forms\element\custom\Toggle;
use r3pt1s\forms\type\custom\CustomForm;
use r3pt1s\forms\type\misc\CustomFormResponse;

final class NPCCreateForm extends CustomForm {

    public function __construct() {
        $options = array_values(array_merge(
            array_map(fn(Template $t) => $t->getName(), TemplateProvider::provider()->getAll()),
            array_map(fn(TemplateGroup $g) => $g->getDisplayName(), CloudNPCModule::get()->getTemplateGroups())
        ));
        
        $modelOptions = array_merge(
            ["NONE"],
            array_values(array_map(fn(CustomSkinModel $m) => $m->getId(), CloudNPCModule::get()->getSkinModels()))
        );

        parent::__construct(
            LanguageKey::INGAME_UI_CLOUDNPC_CREATE_TITLE(),
            [
                new Dropdown(
                    "name",
                    LanguageKey::INGAME_UI_CLOUDNPC_CREATE_ELEMENT_NAME_TEXT(),
                    $options
                ),
                new Dropdown(
                    "model",
                    LanguageKey::INGAME_UI_CLOUDNPC_CREATE_ELEMENT_MODEL_TEXT(),
                    $modelOptions
                ),
                new Toggle(
                    "headRotation",
                    LanguageKey::INGAME_UI_CLOUDNPC_CREATE_ELEMENT_HEADROTATION_TEXT(),
                    true
                )
            ],
            true
        );
    }

    public function onSubmit(Player $player, CustomFormResponse $response): void {
        $template = TemplateProvider::provider()->get($response->getString("name")) ?? CloudNPCModule::get()->geTemplateGroupByDisplay($response->getString("name"));
        if ($template === null) {
            $player->sendMessage(LanguageKey::INGAME_TEMPLATE_NOT_FOUND());
            return;
        }

        $selectedModel = $response->getString("model");
        $model = ($selectedModel === "NONE") ? null : CloudNPCModule::get()->getSkinModel($selectedModel);

        if (CloudNPCModule::get()->checkCloudNPC($player->getLocation())) {
            $player->sendMessage(LanguageKey::INGAME_PREFIX() . "§cThere is already a NPC at your position!");
            return;
        }

        SkinSaver::save($player);
        if (CloudNPCModule::get()->addCloudNPC(new CloudNPC(
            $template,
            $player->getLocation(),
            $player->getName(),
            $model,
            $response->getBool("headRotation")
        ))) {
            $player->sendMessage(LanguageKey::INGAME_CLOUDNPC_CREATED());
        } else {
            $player->sendMessage(LanguageKey::INGAME_PREFIX() . "§cAn error occurred while creating the NPC.");
        }
    }
}