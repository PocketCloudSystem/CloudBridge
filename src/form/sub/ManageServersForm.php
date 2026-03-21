<?php

namespace pocketcloud\cloud\bridge\form\sub;

use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\form\sub\server\ServerInfoForm;
use pocketcloud\cloud\bridge\form\sub\server\StartServerForm;
use pocketcloud\cloud\bridge\form\sub\server\StopServerForm;
use pocketcloud\cloud\bridge\form\util\FormConstants;
use pocketcloud\cloud\bridge\form\util\FormFilterMechanism;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\util\Utils;
use pocketmine\player\Player;
use r3pt1s\forms\element\menu\MenuOption;
use r3pt1s\forms\element\text\Divider;
use r3pt1s\forms\type\menu\MenuForm;

final class ManageServersForm extends MenuForm {

    private ?FormFilterMechanism $mechanism;

    public function __construct(
        ?FormFilterMechanism $mechanism = null,
        private int $requestedPage = 0
    ) {
        $this->mechanism = $mechanism;

        $runningServers = $mechanism?->filter(CloudServerProvider::provider()->getAll()) ?? CloudServerProvider::provider()->getAll();
        $maxPages = max(1, intval(ceil(count($runningServers) / FormConstants::MAX_ENTRIES_PER_PAGE)));
      
        if ($this->requestedPage >= $maxPages) $this->requestedPage = $maxPages - 1;
        if ($this->requestedPage < 0) $this->requestedPage = 0;

        $displayedServers = array_slice(
            array_values($runningServers),
            $this->requestedPage * FormConstants::MAX_ENTRIES_PER_PAGE,
            FormConstants::MAX_ENTRIES_PER_PAGE
        );

        $displayedPage = $this->requestedPage + 1;

        $elements = [
            new MenuOption(LanguageKey::INGAME_UI_MANAGE_SERVER_BUTTON_START(), extraData: ["action" => "start"]),
            new MenuOption(LanguageKey::INGAME_UI_MANAGE_SERVER_BUTTON_STOP(), extraData: ["action" => "stop"]),
            new MenuOption(LanguageKey::INGAME_UI_MANAGE_SERVER_BUTTON_INFO(), extraData: ["action" => "info"]),
            new MenuOption(LanguageKey::INGAME_UI_MANAGE_SERVER_BUTTON_LIST() . " §c(filter)", extraData: ["action" => "filter"]),
            new Divider(),
        ];

        $elements = array_merge(
            $elements,
            array_map(
                fn(CloudServer $server) => new MenuOption(
                    Utils::multiLine(
                        $server->getName(),
                        $server->getServerStatus()->getDisplay() .
                        " §8| §a" .
                        $server->getPlayerCount() .
                        "§8/§c" .
                        $server->getServerData()->getMaxPlayers()
                    ),
                    extraData: ["serverName" => $server->getName()]
                ),
                $this->sortServersOverall($displayedServers)
            )
        );

        if ($this->requestedPage < $maxPages - 1) $elements[] = new MenuOption("§aNext Page", extraData: ["action" => FormConstants::ACTION_NEXT_PAGE]);
        if ($this->requestedPage > 0) $elements[] = new MenuOption("§cPrevious Page", extraData: ["action" => FormConstants::ACTION_PREVIOUS_PAGE]);

        parent::__construct(
            LanguageKey::INGAME_UI_MANAGE_SERVER_TITLE(),
            Utils::multiLine(
                "§7Running Servers" .
                ($mechanism !== null ? " §8(§cfiltered§8)" : "") .
                ": §b" .
                count($runningServers),
                "§7Page§8: §a" . $displayedPage . "§8/§c" . $maxPages
            ),
            $elements
        );
    }

    private function sortServersOverall(array $servers): array {
        usort($servers, function (CloudServer $a, CloudServer $b): int {
            $nameA = $a->getName();
            $nameB = $b->getName();

            $templateNameA = ($nameAParts = explode("-", $nameA))[0] ?? null;
            $templateNameB = ($nameBParts = explode("-", $nameB))[0] ?? null;
            if ($templateNameA !== null && $templateNameB !== null) {
                $templateCompare = strcmp($templateNameA, $templateNameB);
                if ($templateCompare !== 0) return $templateCompare;
                if (count($nameAParts) > 1 && count($nameBParts) > 1) return (int) $nameAParts[1] <=> (int) $nameBParts[1];
            }

            return strcmp($nameA, $nameB);
        });

        return $servers;
    }

    public function onSubmit(Player $player, int $index, MenuOption $option): void {
        $action = $option->get("action");
        if ($action !== null) {
            match ($action) {
                "start" => $player->sendForm(new StartServerForm()),
                "stop" => $player->sendForm(new StopServerForm()),
                "info" => $player->sendForm(new ServerInfoForm()),
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

        $serverName = $option->get("serverName");
        if ($serverName !== null) {
            $server = CloudServerProvider::provider()->get($serverName);
            $player->sendForm(new ServerInfoForm($server));
        }
    }
}