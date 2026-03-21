<?php

namespace pocketcloud\cloud\bridge\form\sub\server;

use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\form\sub\ManagePlayersForm;
use pocketcloud\cloud\bridge\form\sub\template\TemplateInfoForm;
use pocketcloud\cloud\bridge\form\util\FormFilterMechanism;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketmine\player\Player;
use r3pt1s\forms\builder\MenuFormBuilder;
use r3pt1s\forms\element\custom\Dropdown;
use r3pt1s\forms\element\menu\MenuOption;
use r3pt1s\forms\type\custom\CustomForm;
use r3pt1s\forms\type\menu\MenuForm;
use r3pt1s\forms\type\misc\CustomFormResponse;

final class ServerInfoForm extends CustomForm {

    /** @var array<CloudServer> */
    private array $servers;

    public function __construct(?CloudServer $preSelected = null) {
        $this->servers = array_values(CloudServerProvider::provider()->getAll());

        $defaultIndex = 0;
        if ($preSelected !== null) {
            foreach ($this->servers as $i => $s) {
                if ($s->getName() === $preSelected->getName()) {
                    $defaultIndex = $i;
                    break;
                }
            }
        }

        parent::__construct(
            LanguageKey::INGAME_UI_MANAGE_SERVER_SUB_INFO_TITLE(),
            [
                new Dropdown(
                    "server",
                    LanguageKey::INGAME_UI_MANAGE_SERVER_SUB_INFO_DROPDOWN_TEXT(),
                    array_map(fn(CloudServer $s) => $s->getName(), $this->servers),
                    $defaultIndex
                )
            ]
        );
    }

    public function onSubmit(Player $player, CustomFormResponse $response): void {
        $server = $this->servers[$response->getInt("server")] ?? null;
        if ($server === null) {
            $player->sendMessage(LanguageKey::INGAME_SERVER_NOT_FOUND());
            return;
        }

        $player->sendForm($this->serverInfoViewForm($server));
    }

    private function serverInfoViewForm(CloudServer $server): MenuForm {
        $body = [
            "§7Template: §b" . $server->getTemplateName() . " §8(§b" . $server->getTemplateName() . "§8/§b" . $server->getId() . "§8)",
            "§7Player Count: §b" . $server->getPlayerCount() . "§8/§c" . $server->getServerData()->getMaxPlayers(),
            "§7Server UUID: §b" . $server->getServerUuid(),
            "§7Port: §b" . $server->getServerData()->getPort(),
            "§7Status: §b" . $server->getServerStatus()->getDisplay()
        ];

        return MenuFormBuilder::create(
            $server->getName(),
            implode("\n", $body),
            [
                new MenuOption("Transfer"),
                new MenuOption("Manage Players"),
                new MenuOption("View Template"),
                new MenuOption("Save"),
                new MenuOption("Stop")
            ],
            function (Player $player, int $index) use($server): void {
                if ($index == 0) {
                    $player->chat("/transfer " . $server->getName());
                } else if ($index == 1) {
                    $player->sendForm(new ManagePlayersForm(FormFilterMechanism::SERVER($server->getName()), 1));
                } else if ($index == 2) {
                    $player->sendForm(new TemplateInfoForm($server->getTemplate()));
                } else if ($index == 3) {
                    $player->chat("/cloud save " . $server->getName());
                } else if ($index == 4) {
                    $player->chat("/cloud stop " . $server->getName());
                }
            }
        )->build();
    }
}