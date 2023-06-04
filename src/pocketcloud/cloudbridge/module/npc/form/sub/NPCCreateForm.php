<?php

namespace pocketcloud\cloudbridge\module\npc\form\sub;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\template\Template;
use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\module\npc\CloudNPC;
use pocketcloud\cloudbridge\module\npc\CloudNPCManager;
use pocketcloud\cloudbridge\util\SkinSaver;
use pocketmine\player\Player;

class NPCCreateForm extends CustomForm {

    public function __construct() {
        parent::__construct(
            Language::current()->translate("inGame.ui.cloudnpc.create.title"),
            [new Dropdown("name", Language::current()->translate("inGame.ui.cloudnpc.create.element.name.text"), array_map(fn(Template $template) => $template->getName(), CloudAPI::getInstance()->getTemplates()))],
            function(Player $player, CustomFormResponse $response): void {
                $template = array_values(CloudAPI::getInstance()->getTemplates())[$response->getInt("name")] ?? null;
                if ($template !== null) {
                    if (!CloudNPCManager::getInstance()->checkCloudNPC($player->getPosition())) {
                        $player->sendMessage(Language::current()->translate("inGame.cloudnpc.created"));
                        SkinSaver::save($player);
                        CloudNPCManager::getInstance()->addCloudNPC(new CloudNPC(
                            $template,
                            $player->getPosition(),
                            $player->getName()
                        ));
                    }
                } else $player->sendMessage(Language::current()->translate("inGame.template.not.found"));
            }
        );
    }
}