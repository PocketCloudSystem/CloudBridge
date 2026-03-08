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

    public function __construct() {
        $this->templates = array_values(TemplateProvider::provider()->getAll());

        parent::__construct(
            LanguageKey::INGAME_UI_MANAGE_SERVER_SUB_START_TITLE(),
            [
                new Dropdown(
                    "template",
                    LanguageKey::INGAME_UI_MANAGE_SERVER_SUB_START_DROPDOWN_TEXT(),
                    array_map(fn(Template $t) => $t->getName(), $this->templates)
                ),
                new Slider(
                    "count",
                    LanguageKey::INGAME_UI_MANAGE_SERVER_SUB_START_COUNT_TEXT(),
                    1.0, 10.0, 1.0, 1.0
                )
            ]
        );
    }

    public function onSubmit(Player $player, CustomFormResponse $response): void {
        $template = $this->templates[$response->getInt("template")] ?? null;
        if ($template === null) {
            $player->sendMessage(LanguageKey::INGAME_TEMPLATE_NOT_FOUND());
            return;
        }

        $count = $response->getInt("count");
        $running = count(CloudServerProvider::provider()->getAll($template));

        if ($running + $count > $template->getMaxServerCount()) {
            $player->sendMessage(LanguageKey::INGAME_MAX_SERVERS_REACHED()->translate([$template->getName()]));
            return;
        }

        CloudServerProvider::provider()->start($template->getName(), $count);
    }
}