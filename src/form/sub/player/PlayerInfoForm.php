<?php

namespace pocketcloud\cloud\bridge\form\sub\player;

use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\util\Utils;
use pocketmine\player\Player;
use r3pt1s\forms\element\custom\Dropdown;
use r3pt1s\forms\type\custom\CustomForm;
use r3pt1s\forms\type\misc\CustomFormResponse;

final class PlayerInfoForm extends CustomForm {

    /** @var array<CloudPlayer> */
    private array $players;

    public function __construct(?CloudPlayer $preSelected = null) {
        $this->players = array_values(CloudPlayerProvider::provider()->getAll());

        $defaultIndex = 0;
        if ($preSelected !== null) {
            foreach ($this->players as $i => $p) {
                if ($p->getName() === $preSelected->getName()) {
                    $defaultIndex = $i;
                    break;
                }
            }
        }

        parent::__construct(
            LanguageKey::INGAME_UI_MANAGE_PLAYER_SUB_INFO_TITLE(),
            [
                new Dropdown(
                    "target",
                    LanguageKey::INGAME_UI_MANAGE_PLAYER_SUB_INFO_DROPDOWN_TEXT(),
                    array_map(fn(CloudPlayer $p) => $p->getName(), $this->players),
                    $defaultIndex
                )
            ]
        );
    }

    public function onSubmit(Player $player, CustomFormResponse $response): void {
        $target = $this->players[$response->getInt("target")] ?? null;
        if ($target === null) {
            $player->sendMessage(LanguageKey::INGAME_PLAYER_NOT_FOUND());
            return;
        }

        $player->sendMessage(Utils::multiLine(
            "§8━━━━━━━━━━━━━━━━━━━━━━━━━",
            "§b§l" . $target->getName(),
            "§8━━━━━━━━━━━━━━━━━━━━━━━━━",
            "§7XboxUserId§8: §b" . $target->getXboxUserId(),
            "§7UUID§8:       §b" . $target->getUniqueId(),
            "§7Address§8:    §b" . $target->getAddress(),
            "§7Server§8:     §b" . ($target->getCurrentServerName() ?? "§c/"),
            "§7Proxy§8:      §b" . ($target->getCurrentProxyName() ?? "§c/"),
            "§8━━━━━━━━━━━━━━━━━━━━━━━━━"
        ));
    }
}