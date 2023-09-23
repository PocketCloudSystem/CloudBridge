<?php

namespace pocketcloud\cloudbridge\form\selection;

use dktapps\pmforms\BaseForm;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketcloud\cloudbridge\language\Language;
use pocketmine\player\Player;

class CloudSelectionForm extends MenuForm {

    public function __construct(
        private readonly BaseForm $redirectName,
        private readonly BaseForm $redirectSelection
    ) {
        parent::__construct(
            Language::current()->translate("inGame.ui.general.selection.title"),
            Language::current()->translate("inGame.ui.general.selection.text"),
            [
                new MenuOption(Language::current()->translate("inGame.ui.general.selection.option.name")),
                new MenuOption(Language::current()->translate("inGame.ui.general.selection.option.selection"))
            ],
            function(Player $player, int $data): void {
                if ($data == 0) {
                    $player->sendForm($this->redirectName);
                } else {
                    $player->sendForm($this->redirectSelection);
                }
            }
        );
    }
}