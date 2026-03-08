<?php

namespace pocketcloud\cloud\bridge\form\sub\server;

use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\util\Utils;
use pocketmine\player\Player;
use r3pt1s\forms\element\custom\Dropdown;
use r3pt1s\forms\type\custom\CustomForm;
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

        $player->sendMessage(Utils::multiLine(
            "§8━━━━━━━━━━━━━━━━━━━━━━━━━",
            "§b§l" . $server->getName(),
            "§8━━━━━━━━━━━━━━━━━━━━━━━━━",
            "§7Template§8: §e" . $server->getTemplateName(),
            "§7Status§8:   " . $server->getServerStatus()->getDisplay(),
            "§7Players§8:  §a" . $server->getPlayerCount() . "§8/§c" . $server->getServerData()->getMaxPlayers(),
            "§7Port§8:     §b" . $server->getServerData()->getPort(),
            "§8━━━━━━━━━━━━━━━━━━━━━━━━━"
        ));
    }
}