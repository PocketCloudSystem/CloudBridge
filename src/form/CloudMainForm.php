<?php

namespace pocketcloud\cloud\bridge\form;

use pocketcloud\cloud\bridge\api\CloudAPI;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\form\sub\ManageModulesForm;
use pocketcloud\cloud\bridge\form\sub\ManagePlayersForm;
use pocketcloud\cloud\bridge\form\sub\ManageServersForm;
use pocketcloud\cloud\bridge\form\sub\ManageTemplatesForm;
use pocketcloud\cloud\bridge\form\sub\server\ServerInfoForm;
use pocketcloud\cloud\bridge\form\util\FormFilterMechanism;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\network\packet\data\LogType;
use pocketcloud\cloud\bridge\util\CloudEnvironmentConfig;
use pocketmine\player\Player;
use r3pt1s\forms\builder\CustomFormBuilder;
use r3pt1s\forms\element\custom\Dropdown;
use r3pt1s\forms\element\custom\Input;
use r3pt1s\forms\element\menu\MenuOption;
use r3pt1s\forms\element\text\Divider;
use r3pt1s\forms\element\text\Header;
use r3pt1s\forms\type\custom\CustomForm;
use r3pt1s\forms\type\menu\MenuForm;
use r3pt1s\forms\type\misc\CustomFormResponse;
use UnitEnum;

final class CloudMainForm extends MenuForm {

    public function __construct() {
        parent::__construct(
            LanguageKey::INGAME_UI_CLOUD_MAIN_TITLE(),
            "§7Thank you for using §3Pocket§bCloud§8.",
            [
                new Divider(),
                new Header("§cGeneral"),
                new MenuOption(LanguageKey::INGAME_UI_CLOUD_MAIN_BUTTON_MANAGE_SERVER(), extraData: ["action" => "manage_servers"]),
                new MenuOption(LanguageKey::INGAME_UI_CLOUD_MAIN_BUTTON_MANAGE_TEMPLATE(), extraData: ["action" => "manage_templates"]),
                new MenuOption(LanguageKey::INGAME_UI_CLOUD_MAIN_BUTTON_MANAGE_PLAYER(), extraData: ["action" => "manage_players"]),
                new Divider(),
                new Header("§cCurrent Server"),
                new MenuOption("Info", extraData: ["action" => "current:info"]),
                new MenuOption("Manage players", extraData: ["action" => "current:manage_players"]),
                new MenuOption(LanguageKey::INGAME_UI_CLOUD_MAIN_BUTTON_MANAGE_MODULE(), extraData: ["action" => "current:manage_modules"]),
                new MenuOption("Save", extraData: ["action" => "current:save"]),
                new MenuOption("Stop", extraData: ["action" => "current:stop"]),
                new Divider(),
                new Header("§cOther"),
                new MenuOption("Write a message to the cloud console", extraData: ["action" => "other:msg_cloud"])
            ]
        );
    }

    public function onSubmit(Player $player, int $index, MenuOption $option): void {
        $action = $option->get("action");
        match ($action) {
            "manage_servers" => $player->sendForm(new ManageServersForm()),
            "manage_templates" => $player->sendForm(new ManageTemplatesForm()),
            "manage_players" => $player->sendForm(new ManagePlayersForm()),
            "current:info" => $player->sendForm(new ServerInfoForm(CloudServerProvider::provider()->current())),
            "current:manage_players" => $player->sendForm(new ManagePlayersForm(FormFilterMechanism::SERVER(CloudEnvironmentConfig::getServerName()))),
            "current:manage_modules" => $player->sendForm(new ManageModulesForm()),
            "current:save" => $player->chat("/cloud save " . CloudEnvironmentConfig::getServerName()),
            "current:stop" => $player->chat("/cloud stop " . CloudEnvironmentConfig::getServerName()),
            "other:msg_cloud" => $player->sendForm($this->messageCloudConsole()),
            default => null
        };
    }

    private function messageCloudConsole(): CustomForm {
        return CustomFormBuilder::create(
            "Write a message to the cloud console",
            [
                new Input("message", "Message", "..."),
                new Dropdown("log_type", "Log Type", array_map(fn(UnitEnum $e) => strtoupper($e->name), LogType::cases()))
            ],
            true,
            function (Player $player, CustomFormResponse $response): void {
                $message = $response->getString("message");
                $logType = LogType::fromName($response->getString("log_type"));
                CloudAPI::get()->logConsole($message, $logType);
            }
        )->build();
    }
}