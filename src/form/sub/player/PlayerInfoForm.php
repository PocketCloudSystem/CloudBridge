<?php

namespace pocketcloud\cloud\bridge\form\sub\player;

use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\form\sub\server\ServerInfoForm;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\network\packet\type\TextType;
use pocketmine\player\Player;
use r3pt1s\forms\builder\CustomFormBuilder;
use r3pt1s\forms\builder\MenuFormBuilder;
use r3pt1s\forms\element\custom\Dropdown;
use r3pt1s\forms\element\custom\Input;
use r3pt1s\forms\element\menu\MenuOption;
use r3pt1s\forms\type\custom\CustomForm;
use r3pt1s\forms\type\menu\MenuForm;
use r3pt1s\forms\type\misc\CustomFormResponse;
use UnitEnum;

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

        $player->sendForm($this->playerInfoViewForm($target));
    }

    private function playerInfoViewForm(CloudPlayer $target): MenuForm {
        $body = [
            "§7XboxUserId: §b" . $target->getXboxUserId(),
            "§7UniqueId: §b" . $target->getUniqueId(),
            "§7CurrentServer: §b" . ($target->getCurrentServerName() ?? "§cNone"),
            "§7CurrentProxy: §b" . ($target->getCurrentProxyName() ?? "§cNone"),
        ];

        return MenuFormBuilder::create(
            $target->getName(),
            implode("\n", $body),
            [
                new MenuOption("Send Message"),
                new MenuOption("Kick"),
                new MenuOption("View Current Server"),
                new MenuOption("View Current Proxy")
            ],
            function (Player $player, int $index) use($target): void {
                if ($index == 0) {
                    $player->sendForm($this->playerSendForm($target));
                } else if ($index == 1) {
                    $player->sendForm(new KickPlayerForm($target));
                } else {
                    $player->sendForm(new ServerInfoForm($index == 2 ? $target->getCurrentServer() : $target->getCurrentProxy()));
                }
            }
        )->build();
    }

    private function playerSendForm(CloudPlayer $target): CustomForm {
        return CustomFormBuilder::create(
            $target->getName(),
            [
                new Dropdown("type", "Text Type", array_map(fn(UnitEnum $e) => strtoupper($e->name), TextType::cases())),
                new Input("message", "Message", "..."),
            ],
            true,
            function (Player $player, CustomFormResponse $response) use ($target): void {
                $type = TextType::fromName($response->getString("type"));
                $message = $response->getString("message");
                $player->chat("/cloud text " . $target->getName() . " " . $type->getName() . " " . $message);
            },
            fn(Player $player) => $player->sendForm($this->playerInfoViewForm($target))
        )->build();
    }
}