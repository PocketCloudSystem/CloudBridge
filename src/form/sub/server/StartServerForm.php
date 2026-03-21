<?php

namespace pocketcloud\cloud\bridge\form\sub\server;

use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketmine\player\Player;
use r3pt1s\forms\element\custom\Dropdown;
use r3pt1s\forms\element\custom\Slider;
use r3pt1s\forms\type\custom\CustomForm;
use r3pt1s\forms\type\misc\CustomFormResponse;

final class StartServerForm extends CustomForm {

    /** @var array<Template> */
    private array $templates;

    public function __construct(?Template $preSelected = null) {
        $this->templates = array_values(TemplateProvider::provider()->getAll());

        $defaultIndex = 0;
        if ($preSelected !== null) {
            foreach ($this->templates as $i => $p) {
                if ($p->getName() === $preSelected->getName()) {
                    $defaultIndex = $i;
                    break;
                }
            }
        }

        parent::__construct(
            LanguageKey::INGAME_UI_MANAGE_SERVER_SUB_START_TITLE(),
            [
                new Dropdown(
                    "template",
                    LanguageKey::INGAME_UI_MANAGE_SERVER_SUB_START_DROPDOWN_TEXT(),
                    array_map(fn(Template $t) => $t->getName(), $this->templates),
                    $defaultIndex
                ),
                new Slider(
                    "count",
                    LanguageKey::INGAME_UI_MANAGE_SERVER_SUB_START_COUNT_TEXT(),
                    1.0, 20.0, 1.0, 1.0
                )
            ],
            true
        );
    }

    public function onSubmit(Player $player, CustomFormResponse $response): void {
        $player->chat("/cloud start " . $response->getString("template") . " " . $response->getInt("count"));
    }
}