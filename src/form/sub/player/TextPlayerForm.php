<?php

namespace pocketcloud\cloud\bridge\form\sub\player;

use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketcloud\cloud\bridge\network\packet\data\TextType;
use pocketmine\player\Player;
use r3pt1s\forms\element\custom\Dropdown;
use r3pt1s\forms\element\custom\Input;
use r3pt1s\forms\type\custom\CustomForm;
use r3pt1s\forms\type\misc\CustomFormResponse;

final class TextPlayerForm extends CustomForm {

    /** @var array<CloudPlayer> */
    private array $players;
    /** @var array<TextType> */
    private array $textTypes;

    public function __construct(?CloudPlayer $preSelected = null) {
        $this->players = array_values(CloudPlayerProvider::provider()->getAll());
        $this->textTypes = TextType::cases();

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
            LanguageKey::INGAME_UI_MANAGE_PLAYER_SUB_TEXT_TITLE(),
            [
                new Dropdown(
                    "target",
                    LanguageKey::INGAME_UI_MANAGE_PLAYER_SUB_TEXT_DROPDOWN_TEXT(),
                    array_map(fn(CloudPlayer $p) => $p->getName(), $this->players),
                    $defaultIndex
                ),
                new Input(
                    "message",
                    LanguageKey::INGAME_UI_MANAGE_PLAYER_SUB_TEXT_MESSAGE_TEXT(),
                    "Hello World!"
                ),
                new Dropdown(
                    "type",
                    LanguageKey::INGAME_UI_MANAGE_PLAYER_SUB_TEXT_TEXT_TYPE_TEXT(),
                    array_map(fn(TextType $t) => $t->name, $this->textTypes)
                )
            ]
        );
    }

    public function onSubmit(Player $player, CustomFormResponse $response): void {
        $target = $this->players[$response->getInt("target")] ?? null;
        $message = trim($response->getString("message"));

        if ($target === null) {
            $player->sendMessage(LanguageKey::INGAME_PLAYER_NOT_FOUND());
            return;
        }

        if ($message === "") return;

        $textType = $this->textTypes[$response->getInt("type")] ?? TextType::MESSAGE;
        $target->send($message, $textType);
        $player->sendMessage(LanguageKey::INGAME_TEXT_SUCCESSFUL_MESSAGE()->translate([$target->getName(), $message]));
    }
}