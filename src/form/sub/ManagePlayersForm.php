<?php

namespace pocketcloud\cloud\bridge\form\sub;

use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\form\sub\player\KickPlayerForm;
use pocketcloud\cloud\bridge\form\sub\player\PlayerInfoForm;
use pocketcloud\cloud\bridge\form\sub\player\TextPlayerForm;
use pocketcloud\cloud\bridge\form\util\FormConstants;
use pocketcloud\cloud\bridge\form\util\FormFilterMechanism;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\util\Utils;
use pocketmine\player\Player;
use r3pt1s\forms\element\menu\MenuOption;
use r3pt1s\forms\element\text\Divider;
use r3pt1s\forms\type\menu\MenuForm;

final class ManagePlayersForm extends MenuForm {

    private ?FormFilterMechanism $mechanism;

    public function __construct(
        ?FormFilterMechanism $mechanism = null,
        private int $requestedPage = 0
    ) {
        $this->mechanism = $mechanism;

        $allPlayers = $mechanism?->filter(CloudPlayerProvider::provider()->getAll()) ?? CloudPlayerProvider::provider()->getAll();
        $maxPages = max(1, intval(ceil(count($allPlayers) / FormConstants::MAX_SERVERS_PER_PAGE)));

        if ($this->requestedPage >= $maxPages) $this->requestedPage = $maxPages - 1;
        if ($this->requestedPage < 0) $this->requestedPage = 0;

        $displayed = array_slice(array_values($allPlayers), $this->requestedPage * FormConstants::MAX_SERVERS_PER_PAGE, FormConstants::MAX_SERVERS_PER_PAGE);
        $displayedPage = $this->requestedPage + 1;

        $elements = [
            new MenuOption(LanguageKey::INGAME_UI_MANAGE_PLAYER_BUTTON_TEXT(), extraData: ["action" => "text"]),
            new MenuOption(LanguageKey::INGAME_UI_MANAGE_PLAYER_BUTTON_KICK(), extraData: ["action" => "kick"]),
            new MenuOption(LanguageKey::INGAME_UI_MANAGE_PLAYER_BUTTON_INFO(), extraData: ["action" => "info"]),
            new MenuOption("§6" . LanguageKey::INGAME_UI_MANAGE_PLAYER_BUTTON_LIST() . " §8(filter)", extraData: ["action" => "filter"]),
            new Divider(),
        ];

        $elements = array_merge(
            $elements,
            array_map(
                fn(CloudPlayer $p) => new MenuOption(
                    Utils::multiLine(
                        "§e" . $p->getName(),
                        "§b" . ($p->getCurrentServerName() ?? "§c-")
                    ),
                    extraData: ["playerName" => $p->getName()]
                ),
                $displayed
            )
        );

        if ($this->requestedPage <
            $maxPages -
            1) $elements[] = new MenuOption("Next Page", extraData: ["action" => FormConstants::ACTION_NEXT_PAGE]);
        if ($this->requestedPage >
            0) $elements[] = new MenuOption("Previous Page", extraData: ["action" => FormConstants::ACTION_PREVIOUS_PAGE]);

        parent::__construct(
            LanguageKey::INGAME_UI_MANAGE_PLAYER_TITLE(),
            Utils::multiLine(
                "§7Online Players" . ($mechanism !== null ? " §8(§cfiltered§8)" : "") . ": §b" . count($allPlayers),
                "§7Page§8: §a" . $displayedPage . "§8/§c" . $maxPages
            ),
            $elements
        );
    }

    public function onSubmit(Player $player, int $index, MenuOption $option): void {
        $action = $option->get("action");
        if ($action !== null) {
            match ($action) {
                "text" => $player->sendForm(new TextPlayerForm()),
                "kick" => $player->sendForm(new KickPlayerForm()),
                "info" => $player->sendForm(new PlayerInfoForm()),
                "filter" => FormFilterMechanism::awaitMechanismOption($player)->onCompletion(
                    fn(?FormFilterMechanism $mechanism) => $player->sendForm(new self($mechanism, $this->requestedPage)),
                    fn() => null
                ),

                FormConstants::ACTION_NEXT_PAGE => $player->sendForm(new self($this->mechanism, $this->requestedPage + 1)),
                FormConstants::ACTION_PREVIOUS_PAGE => $player->sendForm(new self($this->mechanism, $this->requestedPage - 1)),
                default => null,
            };
            return;
        }

        $playerName = $option->get("playerName");
        if ($playerName !== null) {
            $cloudPlayer = CloudPlayerProvider::provider()->get($playerName);
            if ($cloudPlayer !== null) {
                $player->sendForm(new PlayerInfoForm($cloudPlayer));
            } else {
                $player->sendMessage(LanguageKey::INGAME_PLAYER_NOT_FOUND());
            }
        }
    }
}