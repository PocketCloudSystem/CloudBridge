<?php

namespace pocketcloud\cloudbridge\form;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketcloud\cloudbridge\form\sub\CloudManageModulesSubForm;
use pocketcloud\cloudbridge\form\sub\CloudManagePlayersSubForm;
use pocketcloud\cloudbridge\form\sub\CloudManageServersSubForm;
use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\network\packet\impl\types\LogType;
use pocketmine\player\Player;

class CloudMainForm extends MenuForm {

    public function __construct() {
        parent::__construct(
            Language::current()->translate("inGame.ui.cloud.main.title"),
            Language::current()->translate("inGame.ui.cloud.main.text"),
            [
                new MenuOption(Language::current()->translate("inGame.ui.cloud.main.button.manage_server")),
                new MenuOption(Language::current()->translate("inGame.ui.cloud.main.button.manage_player")),
                new MenuOption(Language::current()->translate("inGame.ui.cloud.main.button.manage_module")),
                new MenuOption(Language::current()->translate("inGame.ui.cloud.main.button.save_server")),
                new MenuOption(Language::current()->translate("inGame.ui.cloud.main.button.cloud_log_console"))
            ],
            function(Player $player, int $data): void {
                if ($data == 0) {
                    $player->sendForm(new CloudManageServersSubForm());
                } else if ($data == 1) {
                    $player->sendForm(new CloudManagePlayersSubForm());
                } else if ($data == 2) {
                    $player->sendForm(new CloudManageModulesSubForm());
                } else if ($data == 3) {
                    $player->chat("/cloud save");
                } else if ($data == 4) {
                    $player->sendForm(new CustomForm(
                        Language::current()->translate("inGame.ui.cloud_log_console.title"),
                        [
                            new Input("message", Language::current()->translate("inGame.ui.cloud_log_console.element.message.text")),
                            new Dropdown("type", Language::current()->translate("inGame.ui.cloud_log_console.element.log_type.text"), array_map(fn(LogType $logType) => $logType->getName(), array_values(LogType::getTypes())))
                        ],
                        function(Player $player, CustomFormResponse $response): void {
                            $player->chat("/cloud log " . array_values(LogType::getTypes())[$response->getInt("type")]->getName() . " " . $response->getString("message"));
                        }
                    ));
                }
            }
        );
    }
}