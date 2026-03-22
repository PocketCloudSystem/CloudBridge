<?php

namespace pocketcloud\cloud\bridge\module\impl\npc\form;

use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\module\impl\npc\form\sub\npc\NPCCreateForm;
use pocketcloud\cloud\bridge\module\impl\npc\form\sub\npc\NPCListForm;
use pocketcloud\cloud\bridge\player\PlayerSession;
use pocketmine\player\Player;
use r3pt1s\forms\element\menu\MenuOption;
use r3pt1s\forms\type\menu\MenuForm;

final class NPCMainForm extends MenuForm {

    public function __construct() {
        parent::__construct(
            LanguageKey::INGAME_UI_CLOUDNPC_MAIN_TITLE(),
            LanguageKey::INGAME_UI_CLOUDNPC_MAIN_TEXT(),
            [
                new MenuOption(LanguageKey::INGAME_UI_CLOUDNPC_MAIN_BUTTON_CREATE()),
                new MenuOption(LanguageKey::INGAME_UI_CLOUDNPC_MAIN_BUTTON_REMOVE()),
                new MenuOption(LanguageKey::INGAME_UI_CLOUDNPC_MAIN_BUTTON_LIST()),
                new MenuOption(LanguageKey::INGAME_UI_CLOUDNPC_MAIN_BUTTON_MODELS())
            ]
        );
    }

    public function onSubmit(Player $player, int $index, MenuOption $option): void {
        $session = PlayerSession::get($player);
        if ($index === 0) {
            $player->sendForm(new NPCCreateForm());
        } elseif ($index === 1) {
            if ($session->isAwaitNpcRemoval()) {
                $player->sendMessage(LanguageKey::INGAME_CLOUDNPC_PROCESS_CANCELLED());
                $session->setAwaitNpcRemoval(false);
            } else {
                $player->sendMessage(LanguageKey::INGAME_CLOUDNPC_SELECT());
                $session->setAwaitNpcRemoval(true);
            }
        } elseif ($index === 2) {
            $player->sendForm(new NPCListForm());
        } elseif ($index === 3) {
            $player->sendForm(new SkinModelMainForm());
        }
    }
}