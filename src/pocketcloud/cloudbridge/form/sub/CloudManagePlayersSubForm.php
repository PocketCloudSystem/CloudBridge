<?php

namespace pocketcloud\cloudbridge\form\sub;

use dktapps\pmforms\CustomForm;
use dktapps\pmforms\CustomFormResponse;
use dktapps\pmforms\element\Dropdown;
use dktapps\pmforms\element\Input;
use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\api\object\player\CloudPlayer;
use pocketcloud\cloudbridge\form\selection\CloudSelectionForm;
use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\network\packet\impl\types\TextType;
use pocketmine\player\Player;

class CloudManagePlayersSubForm extends MenuForm {

    public function __construct() {
        parent::__construct(
            Language::current()->translate("inGame.ui.manage_player.title"),
            Language::current()->translate("inGame.ui.manage_player.text"),
            [
                new MenuOption(Language::current()->translate("inGame.ui.manage_player.button.text")),
                new MenuOption(Language::current()->translate("inGame.ui.manage_player.button.kick")),
                new MenuOption(Language::current()->translate("inGame.ui.manage_player.button.list")),
                new MenuOption(Language::current()->translate("inGame.ui.manage_player.button.info")),
            ],
            function(Player $player, int $data): void {
                if ($data == 0) {
                    $player->sendForm(new CloudSelectionForm(
                        new CustomForm(
                            Language::current()->translate("inGame.ui.manage_player.sub.text.title"),
                            [
                                new Input("name", Language::current()->translate("inGame.ui.manage_player.sub.text.name.text")),
                                new Input("message", Language::current()->translate("inGame.ui.manage_player.sub.text.message.text")),
                                new Dropdown("type", Language::current()->translate("inGame.ui.manage_player.sub.text.text_type.text"), array_map(fn(TextType $textType) => $textType->getName(), array_values(TextType::getTypes())))
                            ],
                            function(Player $player, CustomFormResponse $response): void {
                                $player->chat("/cloud text " . $response->getString("name") . " " . array_values(TextType::getTypes())[$response->getInt("type")]->getName() . " " . $response->getString("message"));

                            }
                        ),
                        new CustomForm(
                            Language::current()->translate("inGame.ui.manage_player.sub.text.title"),
                            [
                                new Dropdown("name", Language::current()->translate("inGame.ui.manage_player.sub.text.dropdown.text"), array_map(fn(CloudPlayer $player) => $player->getName(), CloudAPI::getInstance()->getPlayers())),
                                new Input("message", Language::current()->translate("inGame.ui.manage_player.sub.text.message.text")),
                                new Dropdown("type", Language::current()->translate("inGame.ui.manage_player.sub.text.text_type.text"), array_map(fn(TextType $textType) => $textType->getName(), array_values(TextType::getTypes())))
                            ],
                            function(Player $player, CustomFormResponse $response): void {
                                $target = array_values(CloudAPI::playerProvider()->getPlayers())[$response->getInt("name")] ?? null;
                                if ($target !== null) $player->chat("/cloud text " . $target->getName() . " " . array_values(TextType::getTypes())[$response->getInt("type")]->getName() . " " . $response->getString("message"));
                            }
                        )
                    ));
                } else if ($data == 1) {
                    $player->sendForm(new CloudSelectionForm(
                        new CustomForm(
                            Language::current()->translate("inGame.ui.manage_player.sub.kick.title"),
                            [
                                new Input("name", Language::current()->translate("inGame.ui.manage_player.sub.kick.name.text")),
                                new Input("reason", Language::current()->translate("inGame.ui.manage_player.sub.kick.reason.text"))
                            ],
                            function(Player $player, CustomFormResponse $response): void {
                                $player->chat("/cloud kick " . $response->getString("name") . " " . $response->getString("reason"));
                            }
                        ),
                        new CustomForm(
                            Language::current()->translate("inGame.ui.manage_player.sub.kick.title"),
                            [
                                new Dropdown("name", Language::current()->translate("inGame.ui.manage_player.sub.kick.dropdown.text"), array_map(fn(CloudPlayer $player) => $player->getName(), CloudAPI::getInstance()->getPlayers())),
                                new Input("reason", Language::current()->translate("inGame.ui.manage_player.sub.kick.reason.text"))
                            ],
                            function(Player $player, CustomFormResponse $response): void {
                                $target = array_values(CloudAPI::playerProvider()->getPlayers())[$response->getInt("name")] ?? null;
                                if ($target !== null) $player->chat("/cloud kick " . $target->getName() . " " . $response->getString("reason"));
                            }
                        )
                    ));
                } else if ($data == 2) {
                    $player->chat("/cloud list players");
                } else if ($data == 3) {
                    $player->sendForm(new CloudSelectionForm(
                        new CustomForm(
                            Language::current()->translate("inGame.ui.manage_player.sub.info.title"),
                            [new Input("name", Language::current()->translate("inGame.ui.manage_player.sub.info.name.text"))],
                            function(Player $player, CustomFormResponse $response): void {
                                $player->chat("/cloud info player " . $response->getString("name"));
                            }
                        ),
                        new CustomForm(
                            Language::current()->translate("inGame.ui.manage_player.sub.info.title"),
                            [new Dropdown("name", Language::current()->translate("inGame.ui.manage_player.sub.info.dropdown.text"), array_map(fn(CloudPlayer $player) => $player->getName(), CloudAPI::getInstance()->getPlayers()))],
                            function(Player $player, CustomFormResponse $response): void {
                                $target = array_values(CloudAPI::playerProvider()->getPlayers())[$response->getInt("name")] ?? null;
                                if ($target !== null) $player->chat("/cloud info player " . $target->getName());
                            }
                        )
                    ));
                }
            }
        );
    }
}