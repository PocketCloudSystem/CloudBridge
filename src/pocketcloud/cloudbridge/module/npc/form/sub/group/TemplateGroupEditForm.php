<?php

namespace pocketcloud\cloudbridge\module\npc\form\sub\group;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\object\template\Template;
use pocketcloud\cloudbridge\CloudBridge;
use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\module\npc\CloudNPCModule;
use pocketcloud\cloudbridge\module\npc\group\TemplateGroup;
use pocketmine\player\Player;

class TemplateGroupEditForm extends MenuForm {

    public function __construct(TemplateGroup $group) {
        parent::__construct(
            $group->getDisplayName(),
            Language::current()->translate("inGame.ui.template_group.edit.text", $group->getId(), $group->getDisplayName(), (empty($group->getTemplates() ? "§c/" : implode(", ", $group->getTemplates())))),
            [
                new MenuOption(Language::current()->translate("inGame.ui.template_group.edit.button.add_template")),
                new MenuOption(Language::current()->translate("inGame.ui.template_group.edit.button.remove_template")),
                new MenuOption(Language::current()->translate("inGame.ui.template_group.edit.button.change_display"))
            ],
            function (Player $player, int $data) use($group): void {
                if ($data == 0) {
                    $player->sendForm(new MenuForm(
                        Language::current()->translate("inGame.ui.template_group.add_template.title"),
                        Language::current()->translate("inGame.ui.template_group.add_template.text"),
                        array_map(fn(Template $template) => new MenuOption("§e" . $template->getName()), $templates = array_values(CloudAPI::getInstance()->getTemplates())),
                        function (Player $player, int $data) use($group, $templates): void {
                            $template = $templates[$data] ?? null;
                            if ($template !== null) {
                                $group->addTemplate($template->getName());
                                if (CloudNPCModule::get()->editTemplateGroup($group)) {
                                    $player->sendForm(new self($group));
                                } else $player->sendMessage(CloudBridge::getPrefix() . "§cAn error occurred while editing the group: §e" . $group->getId() . "§c. Please report that incident on our discord.");
                            }
                        }
                    ));
                } else if ($data == 1) {
                    if (empty($group->getTemplates())) {
                        $player->sendMessage(CloudBridge::getPrefix() . "§cNo templates available.");
                        $player->sendForm(new self($group));
                        return;
                    }

                    $player->sendForm(new MenuForm(
                        Language::current()->translate("inGame.ui.template_group.remove_template.title"),
                        Language::current()->translate("inGame.ui.template_group.remove_template.text"),
                        array_map(fn(string $template) => new MenuOption("§e" . $template), $templates = $group->getTemplates()),
                        function (Player $player, int $data) use($group, $templates): void {
                            $template = $templates[$data] ?? null;
                            if ($template !== null) {
                                $group->removeTemplate($template);
                                if (CloudNPCModule::get()->editTemplateGroup($group)) {
                                    $player->sendForm(new self($group));
                                } else $player->sendMessage(CloudBridge::getPrefix() . "§cAn error occurred while editing the group: §e" . $group->getId() . "§c. Please report that incident on our discord.");
                            }
                        }
                    ));
                } else if ($data == 2) {
                    $player->sendForm(new CustomForm(
                        Language::current()->translate("inGame.ui.template_group.change_display.title"),
                        [new Input("display", Language::current()->translate("inGame.ui.template_group.change_display.element.display"), $group->getDisplayName(), $group->getDisplayName())],
                        function (Player $player, CustomFormResponse $response) use($group): void {
                            $group->setDisplayName($response->getString("display"));
                            if (CloudNPCModule::get()->editTemplateGroup($group)) {
                                $player->sendForm(new self($group));
                            } else $player->sendMessage(CloudBridge::getPrefix() . "§cAn error occurred while editing the group: §e" . $group->getId() . "§c. Please report that incident on our discord.");
                        }
                    ));
                }
            }
        );
    }
}