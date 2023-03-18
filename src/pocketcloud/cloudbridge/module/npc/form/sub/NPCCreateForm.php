<?php

namespace pocketcloud\cloudbridge\module\npc\form\sub;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\template\Template;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\lib\dktapps\pmforms\CustomForm;
use pocketcloud\cloudbridge\lib\dktapps\pmforms\CustomFormResponse;
use pocketcloud\cloudbridge\lib\dktapps\pmforms\element\Dropdown;
use pocketcloud\cloudbridge\module\npc\CloudNPC;
use pocketcloud\cloudbridge\module\npc\CloudNPCManager;
use pocketcloud\cloudbridge\module\npc\form\NPCMainForm;
use pocketcloud\cloudbridge\skin\SkinSaver;
use pocketcloud\cloudbridge\utils\Message;
use pocketmine\player\Player;

class NPCCreateForm extends CustomForm {

    private array $elements;
    private array $templates = [""];

    public function __construct() {
        if (count($loadedTemplates = CloudAPI::getInstance()->getTemplates()) > 0) $this->templates = array_map(fn(Template $template) => $template->getName(), $loadedTemplates);

        $this->elements = [
            new Dropdown("template", "§7Template", $this->templates)
        ];

        parent::__construct("§8» §eManage NPCs §8| §aCreate §8«", $this->elements, function(Player $player, CustomFormResponse $response): void {
            $template = array_keys($this->templates)[$response->getInt("template")] ?? "";
            if (($template = CloudAPI::getInstance()->getTemplateByName($template)) !== null) {
                 if (!CloudNPCManager::getInstance()->checkCloudNPC($player->getPosition())) {
                     Message::parse(Message::NPC_CREATED)->target($player);
                     SkinSaver::save($player);
                     CloudNPCManager::getInstance()->addCloudNPC(new CloudNPC(
                         $template,
                         $player->getPosition(),
                         $player->getName()
                     ));
                 } else {
                     Message::parse(Message::ALREADY_NPC)->target($player);
                 }
            } else {
                Message::parse(Message::TEMPLATE_EXISTENCE)->target($player);
            }
        }, function(Player $player): void {
            $player->sendForm(new NPCMainForm());
        });
    }
}