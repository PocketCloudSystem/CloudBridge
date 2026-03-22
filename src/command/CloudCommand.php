<?php

namespace pocketcloud\cloud\bridge\command;

use pocketcloud\cloud\bridge\command\util\ParameterType;
use pocketcloud\cloud\bridge\command\util\SubCommandData;
use pocketcloud\cloud\bridge\command\util\SubCommandExecutors;
use pocketcloud\cloud\bridge\form\CloudMainForm;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class CloudCommand extends BaseCloudCommand {

    public function __construct() {
        parent::__construct("cloud", LanguageKey::INGAME_COMMAND_DESCRIPTION_CLOUD(), "/cloud");
        $this->setPermission("pocketcloud.command.cloud");

        $this->registerSubCommand(SubCommandData::create(
            "help",
            "List all the subcommands",
            fn(CommandSender $sender, string $commandLabel, array $args) => $sender->sendMessage($this->buildUsage()),
            optional: true
        ));

        $this->registerSubCommand(SubCommandData::create(
            "start",
            "Start a server",
            SubCommandExecutors::handleStartSub(...),
            [ParameterType::TEMPLATE->with("template", false), ParameterType::INTEGER->with("count")],
            optional: true
        ));

        $this->registerSubCommand(SubCommandData::create(
            "stop",
            "Stop a server",
            SubCommandExecutors::handleStopSub(...),
            [ParameterType::STRING->with("object", false), ParameterType::BOOLEAN->with("forcefully")],
            optional: true
        ));

        $this->registerSubCommand(SubCommandData::create(
            "save",
            "Save a server",
            SubCommandExecutors::handleSaveSub(...),
            [ParameterType::SERVER->with("server", false)],
            optional: true
        ));

        $this->registerSubCommand(SubCommandData::create(
            "enable",
            "Enable a module (locally)",
            SubCommandExecutors::handleEnableModuleSub(...),
            [ParameterType::MODULE->with("module", false)],
            optional: true
        ));

        $this->registerSubCommand(SubCommandData::create(
            "disable",
            "Disable a module (locally)",
            SubCommandExecutors::handleDisableModuleSub(...),
            [ParameterType::MODULE->with("module", false)],
            optional: true
        ));

        $this->registerSubCommand(SubCommandData::create(
            "text",
            "Text a player",
            SubCommandExecutors::handleTextSub(...),
            [ParameterType::CLOUD_PLAYER->with("player", false), ParameterType::TEXT_TYPE->with("type", false), ParameterType::STRING->with("message", false)],
            optional: true
        ));

        $this->registerSubCommand(SubCommandData::create(
            "kick",
            "Kick a player",
            SubCommandExecutors::handleKickSub(...),
            [ParameterType::CLOUD_PLAYER->with("player", false), ParameterType::STRING->with("reason"), ParameterType::STRING->with("disconnectScreenMessage")],
            optional: true
        ));

        $this->registerSubCommand(SubCommandData::create(
            "list",
            "List servers, players, templates, modules, server groups",
            SubCommandExecutors::handleListSub(...),
            [ParameterType::ENUM->with("type", true, ["servers", "templates", "modules", "players", "groups"])],
            optional: true
        ));
    }

    public function run(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) $sender->sendForm(new CloudMainForm());
        else $sender->sendMessage($this->buildUsage());
        return true;
    }
}