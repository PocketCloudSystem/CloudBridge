<?php

namespace pocketcloud\cloud\bridge\module\impl\npc\form\sub\group;

use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\module\impl\npc\CloudNPCModule;
use pocketcloud\cloud\bridge\module\impl\npc\group\TemplateGroup;
use pocketmine\player\Player;
use r3pt1s\forms\builder\CustomFormBuilder;
use r3pt1s\forms\builder\MenuFormBuilder;
use r3pt1s\forms\element\menu\MenuOption;
use r3pt1s\forms\type\menu\MenuForm;
use r3pt1s\forms\type\misc\CustomFormResponse;

final class TemplateGroupEditForm extends MenuForm {

    public function __construct(private readonly TemplateGroup $group) {
        $templates = empty($group->getTemplates()) ? "§c/" : implode(", ", $group->getTemplates());
        parent::__construct(
            $group->getDisplayName(),
            "§7Id: §e" . $group->getId() . " §8| §7Templates: §e" . $templates,
            [
                new MenuOption(LanguageKey::INGAME_UI_TEMPLATE_GROUP_EDIT_BUTTON_ADD_TEMPLATE()),
                new MenuOption(LanguageKey::INGAME_UI_TEMPLATE_GROUP_EDIT_BUTTON_REMOVE_TEMPLATE()),
                new MenuOption(LanguageKey::INGAME_UI_TEMPLATE_GROUP_EDIT_BUTTON_CHANGE_DISPLAY())
            ]
        );
    }
    
    public function onSubmit(Player $player, int $index, MenuOption $option): void {
        if ($index === 0) {
            $templates = array_values(TemplateProvider::provider()->getAll());
            if (empty($templates)) {
                $player->sendMessage(LanguageKey::INGAME_PREFIX() . "§cNo templates available.");
                $player->sendForm(new self($this->group));
                return;
            }

            $player->sendForm(MenuFormBuilder::create(LanguageKey::INGAME_UI_TEMPLATE_GROUP_ADD_TEMPLATE_TITLE(), LanguageKey::INGAME_UI_TEMPLATE_GROUP_ADD_TEMPLATE_TEXT())
                ->elements(array_map(fn(Template $t) => new MenuOption("§e" . $t->getName()), $templates))
                ->onSubmit(function (Player $player, int $index, MenuOption $option) use($templates): void {
                    $template = $templates[$index] ?? null;
                    if ($template !== null) {
                        $this->group->addTemplate($template->getName());
                        if (CloudNPCModule::get()->editTemplateGroup($this->group)) {
                            $player->sendForm(new TemplateGroupEditForm($this->group));
                        } else {
                            $player->sendMessage(LanguageKey::INGAME_PREFIX() . "§cAn error occurred while editing the group: §e" . $this->group->getId());
                        }
                    }
                })
                ->build()
            );
        } elseif ($index === 1) {
            $templates = $this->group->getTemplates();
            if (empty($templates)) {
                $player->sendMessage(LanguageKey::INGAME_PREFIX() . "§cNo templates in this group.");
                $player->sendForm(new self($this->group));
                return;
            }
            
            $player->sendForm(MenuFormBuilder::create(LanguageKey::INGAME_UI_TEMPLATE_GROUP_REMOVE_TEMPLATE_TITLE(), LanguageKey::INGAME_UI_TEMPLATE_GROUP_REMOVE_TEMPLATE_TEXT())
                ->elements(array_map(fn(string $t) => new MenuOption("§e" . $t), $templates))
                ->onSubmit(function (Player $player, int $index, MenuOption $option) use($templates): void {
                    $template = $this->templates[$index] ?? null;
                    if ($template !== null) {
                        $this->group->removeTemplate($template);
                        if (CloudNPCModule::get()->editTemplateGroup($this->group)) {
                            $player->sendForm(new TemplateGroupEditForm($this->group));
                        } else {
                            $player->sendMessage(LanguageKey::INGAME_PREFIX() .
                                "§cAn error occurred while editing the group: §e" .
                                $this->group->getId());
                        }
                    }
                })
                ->build()
            );
        } elseif ($index === 2) {
            $player->sendForm(CustomFormBuilder::create(LanguageKey::INGAME_UI_TEMPLATE_GROUP_CHANGE_DISPLAY_TITLE())
                ->input("display", LanguageKey::INGAME_UI_TEMPLATE_GROUP_CHANGE_DISPLAY_ELEMENT_DISPLAY(), $this->group->getDisplayName(), $this->group->getDisplayName())
                ->onSubmit(function (Player $player, CustomFormResponse $response): void {
                    $this->group->setDisplayName($response->getString("display"));
                    if (CloudNPCModule::get()->editTemplateGroup($this->group)) {
                        $player->sendForm(new TemplateGroupEditForm($this->group));
                    } else {
                        $player->sendMessage(LanguageKey::INGAME_PREFIX() . "§cAn error occurred while editing the group: §e" . $this->group->getId());
                    }
                })
                ->build()
            );
        }
    }
}