<?php

namespace pocketcloud\cloud\bridge\form\sub\template;

use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\form\sub\ManagePlayersForm;
use pocketcloud\cloud\bridge\form\sub\ManageServersForm;
use pocketcloud\cloud\bridge\form\sub\server\StartServerForm;
use pocketcloud\cloud\bridge\form\sub\server\StopServerForm;
use pocketcloud\cloud\bridge\form\util\FormFilterMechanism;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketmine\player\Player;
use r3pt1s\forms\builder\MenuFormBuilder;
use r3pt1s\forms\element\custom\Dropdown;
use r3pt1s\forms\element\menu\MenuOption;
use r3pt1s\forms\type\custom\CustomForm;
use r3pt1s\forms\type\menu\MenuForm;
use r3pt1s\forms\type\misc\CustomFormResponse;

final class TemplateInfoForm extends CustomForm {

    /** @var array<Template> */
    private array $templates;

    public function __construct(?Template $preSelected = null) {
        $this->templates = array_values(TemplateProvider::provider()->getAll());

        $defaultIndex = 0;
        if ($preSelected !== null) {
            foreach ($this->templates as $i => $s) {
                if ($s->getName() === $preSelected->getName()) {
                    $defaultIndex = $i;
                    break;
                }
            }
        }

        parent::__construct(
            LanguageKey::INGAME_UI_MANAGE_TEMPLATE_SUB_INFO_TITLE(),
            [
                new Dropdown(
                    "template",
                    LanguageKey::INGAME_UI_MANAGE_TEMPLATE_SUB_INFO_DROPDOWN_TEXT(),
                    array_map(fn(Template $s) => $s->getName(), $this->templates),
                    $defaultIndex
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

        $player->sendForm($this->templateInfoViewForm($template));
    }

    private function templateInfoViewForm(Template $template): MenuForm {
        $body = [
            "§7Template: §b" . $template->getName() . ($template->getParentServerGroup() !== null ? " §8(§7Server Group: §b" . ($template->getParentServerGroup()?->getName() ?? "Unknown") . "§8)" : ""),
            "§7Player Count: §b" . $template->getPlayerCount() . "§8/§c" . ($template->getMaxPlayerCount() * ($serverCount = $template->getServerCount())),
            "§7Server Count: §b" . $serverCount . "§8/§c" . $template->getMaxServerCount() . " §8(§7min. §b" . $template->getMinServerCount() . " §7running§8)",
            "§7Lobby: " . ($template->isLobby() ? "§aYes" : "§cNo"),
            "§7Maintenance: " . ($template->isMaintenance() ? "§cEnabled" : "§aDisabled"),
            "§7Static: " . ($template->isStatic() ? "§aYes" : "§cNo"),
            "§7AutoStart: " . ($template->isAutoStart() ? "§cEnabled" : "§aDisabled"),
            "§7AlwaysCopyToStaticServers: " . ($template->isAlwaysCopyToStaticServers() ? "§aEnabled" : "§cDisabled"),
            "§7StartNewPercentage: §b" . $template->getStartNewPercentage(),
            "§7TemplateType: §b" . strtolower($template->getTemplateType())
        ];

        return MenuFormBuilder::create(
            $template->getName(),
            implode("\n", $body),
            [
                new MenuOption("Manage Servers"),
                new MenuOption("Manage Players"),
                new MenuOption("Start new server"),
                new MenuOption("Stop a server")
            ],
            function (Player $player, int $index, MenuOption $option) use($template): void {
                if ($index == 0) {
                    $player->sendForm(new ManageServersForm(FormFilterMechanism::TEMPLATE($template->getName())));
                } else if ($index == 1) {
                    $player->sendForm(new ManagePlayersForm(FormFilterMechanism::TEMPLATE($template->getName()), 1));
                } else if ($index == 2) {
                    $player->sendForm(new StartServerForm($template));
                } else if ($index == 3) {
                    $player->sendForm(new StopServerForm());
                }
            }
        )->build();
    }
}