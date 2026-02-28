<?php

namespace pocketcloud\cloud\bridge\form\sub;

use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\form\util\FormConstants;
use pocketcloud\cloud\bridge\form\util\FormFilterMechanism;
use pocketcloud\cloud\bridge\util\Utils;
use pocketmine\player\Player;
use r3pt1s\forms\element\menu\MenuOption;
use r3pt1s\forms\element\text\Divider;
use r3pt1s\forms\type\menu\MenuForm;

final class ManageServersForm extends MenuForm {

    public function __construct(
        ?FormFilterMechanism $mechanism = null,
        private int $requestedPage = 0
    ) {
        $runningServers = $mechanism?->filter(CloudServerProvider::provider()->getAll()) ?? CloudServerProvider::provider()->getAll();
        $maxPages = ceil(count($runningServers) / FormConstants::MAX_SERVERS_PER_PAGE);
        if ($this->requestedPage > $maxPages) $this->requestedPage = ($maxPages - 1);

        $displayedServers = array_slice(
            $runningServers,
            $this->requestedPage * FormConstants::MAX_SERVERS_PER_PAGE,
            FormConstants::MAX_SERVERS_PER_PAGE
        );

        $displayedPage = ($this->requestedPage + 1);

        $elements = [
            new MenuOption("§aStart a server"),
            new MenuOption("§cStop a server"),
            new MenuOption("§6Filter by ..."),
            new Divider()
        ];

        $elements = array_merge(
            $elements,
            array_map(
                fn(CloudServer $server) => new MenuOption(Utils::multiLine(
                    $server->getName(),
                    "§a" . $server->getPlayerCount() . "§8/§c" . $server->getServerData()->getMaxPlayers()
                ), extraData: [$server->getName()]), $this->sortServersOverall($displayedServers)
            )
        );

        if ($this->requestedPage < ($maxPages - 1)) $elements[] = new MenuOption("Next Page", extraData: ["action" => FormConstants::ACTION_NEXT_PAGE]);
        if ($this->requestedPage > 0) $elements[] = new MenuOption("Previous Page", extraData: ["action" => FormConstants::ACTION_PREVIOUS_PAGE]);

        parent::__construct(
            "§eManage Servers",
            Utils::multiLine(
                "§7Running Servers" . ($mechanism !== null ? " §8(§cfiltered§8)" : "") . ": §b" . count($runningServers),
                "§7Page§8: §a" . $displayedPage . "§8/§c" . $maxPages
            ),
            $elements
        );
    }

    public function onSubmit(Player $player, int $index, MenuOption $option): void {
        if ($index == 2) {
            FormFilterMechanism::awaitMechanismOption($player)->onCompletion(
                function (?FormFilterMechanism $mechanism) use($player): void {
                    $player->sendForm(new self($mechanism, $this->requestedPage));
                },
                fn() => null
            );
        } else if ($index > 2) {
            $serverName = $option->get(0) ?? "none";
            $player->sendMessage($serverName);
        }
    }

    private function sortServersOverall(array $servers): array {
        usort($servers, function(CloudServer $a, CloudServer $b): int {
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
}