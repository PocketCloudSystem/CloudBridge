<?php

namespace pocketcloud\cloud\bridge\form\sub;

use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\form\sub\player\PlayerInfoForm;
use pocketcloud\cloud\bridge\form\sub\template\TemplateInfoForm;
use pocketcloud\cloud\bridge\form\util\FormConstants;
use pocketcloud\cloud\bridge\form\util\FormFilterMechanism;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\util\Utils;
use pocketmine\player\Player;
use r3pt1s\forms\element\menu\MenuOption;
use r3pt1s\forms\element\text\Divider;
use r3pt1s\forms\type\menu\MenuForm;

final class ManageTemplatesForm extends MenuForm {

    private ?FormFilterMechanism $mechanism;

    public function __construct(
        ?FormFilterMechanism $mechanism = null,
        private int $requestedPage = 0
    ) {
        $this->mechanism = $mechanism;

        $templates = $mechanism?->filter(TemplateProvider::provider()->getAll()) ?? TemplateProvider::provider()->getAll();
        $maxPages = max(1, intval(ceil(count($templates) / FormConstants::MAX_ENTRIES_PER_PAGE)));

        if ($this->requestedPage >= $maxPages) $this->requestedPage = $maxPages - 1;
        if ($this->requestedPage < 0) $this->requestedPage = 0;

        $displayed = array_slice(array_values($templates), $this->requestedPage * FormConstants::MAX_ENTRIES_PER_PAGE, FormConstants::MAX_ENTRIES_PER_PAGE);
        $displayedPage = $this->requestedPage + 1;

        $elements = [
            new MenuOption(LanguageKey::INGAME_UI_MANAGE_TEMPLATE_BUTTON_INFO(), extraData: ["action" => "info"]),
            new MenuOption(LanguageKey::INGAME_UI_MANAGE_TEMPLATE_BUTTON_LIST() . " §c(filter)", extraData: ["action" => "filter"]),
            new Divider()
        ];

        $elements = array_merge(
            $elements,
            array_map(
                fn(Template $t) => new MenuOption(
                    Utils::multiLine(
                        $t->getName(),
                        "§b" . $t->getPlayerCount() . " Players"
                    ),
                    extraData: ["templateName" => $t->getName()]
                ),
                $displayed
            )
        );

        if ($this->requestedPage < $maxPages - 1) $elements[] = new MenuOption("Next Page", extraData: ["action" => FormConstants::ACTION_NEXT_PAGE]);
        if ($this->requestedPage > 0) $elements[] = new MenuOption("Previous Page", extraData: ["action" => FormConstants::ACTION_PREVIOUS_PAGE]);

        parent::__construct(
            LanguageKey::INGAME_UI_MANAGE_TEMPLATE_TITLE(),
            Utils::multiLine(
                "§7Templates§8:" . ($mechanism !== null ? " §8(§cfiltered§8)" : "") . ": §b" . count($templates),
                "§7Page§8: §a" . $displayedPage . "§8/§c" . $maxPages
            ),
            $elements
        );
    }

    public function onSubmit(Player $player, int $index, MenuOption $option): void {
        $action = $option->get("action");
        if ($action !== null) {
            match ($action) {
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

        $templateName = $option->get("templateName");
        if ($templateName !== null) {
            $template = TemplateProvider::provider()->get($templateName);
            if ($template !== null) {
                $player->sendForm(new TemplateInfoForm($template));
            } else {
                $player->sendMessage(LanguageKey::INGAME_TEMPLATE_NOT_FOUND());
            }
        }
    }
}