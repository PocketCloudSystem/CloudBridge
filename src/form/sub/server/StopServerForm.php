<?php

namespace pocketcloud\cloud\bridge\form\sub\server;

use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketmine\player\Player;
use r3pt1s\forms\element\custom\Dropdown;
use r3pt1s\forms\element\custom\Toggle;
use r3pt1s\forms\type\custom\CustomForm;
use r3pt1s\forms\type\misc\CustomFormResponse;

final class StopServerForm extends CustomForm {

    /** @var array<Template> */
    private array $templates;
    /** @var array<CloudServer> */
    private array $servers;

    public function __construct() {
        $this->templates = array_values(TemplateProvider::provider()->getAll());
        $this->servers = array_values(CloudServerProvider::provider()->getAll());

        $options = array_merge(
            array_map(fn(Template $t) => "§c§lT §r" . $t->getName(), $this->templates),
            array_map(fn(CloudServer $s) => $s->getName(), $this->servers)
        );

        parent::__construct(
            LanguageKey::INGAME_UI_MANAGE_SERVER_SUB_STOP_TITLE(),
            [
                new Dropdown(
                    "target",
                    LanguageKey::INGAME_UI_MANAGE_SERVER_SUB_STOP_DROPDOWN_TEXT(),
                    $options
                ),
                new Toggle(
                    "forcefully",
                    "§cForcefully?",
                    false
                )
            ]
        );
    }

    public function onSubmit(Player $player, CustomFormResponse $response): void {
        $selectedIndex = $response->getInt("target");
        $forcefully = $response->getBool("forcefully");
        $templateCount = count($this->templates);

        if ($selectedIndex < $templateCount) {
            $template = $this->templates[$selectedIndex] ?? null;
            if ($template === null) {
                $player->sendMessage(LanguageKey::INGAME_TEMPLATE_NOT_FOUND());
                return;
            }

            CloudServerProvider::provider()->stop($template, $forcefully);
        } else {
            $server = $this->servers[$selectedIndex - $templateCount] ?? null;
            if ($server === null) {
                $player->sendMessage(LanguageKey::INGAME_SERVER_NOT_FOUND());
                return;
            }

            CloudServerProvider::provider()->stop($server, $forcefully);
        }
    }
}