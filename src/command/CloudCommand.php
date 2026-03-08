<?php

namespace pocketcloud\cloud\bridge\command;

use Closure;
use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\command\util\SubCommandData;
use pocketcloud\cloud\bridge\form\CloudMainForm;
use pocketcloud\cloud\bridge\language\LanguageKey;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\defaults\VanillaCommand;
use pocketmine\lang\Translatable;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\serializer\AvailableCommandsPacketAssembler;
use pocketmine\network\mcpe\protocol\types\command\CommandData;
use pocketmine\network\mcpe\protocol\types\command\CommandHardEnum;
use pocketmine\network\mcpe\protocol\types\command\CommandOverload;
use pocketmine\network\mcpe\protocol\types\command\CommandParameter;
use pocketmine\network\mcpe\protocol\types\command\CommandPermissions;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\Server;

final class CloudCommand extends Command implements PluginOwned {

    /** @var array<string, SubCommandData> */
    private array $subCommands = [];

    public function __construct() {
        parent::__construct("cloud", LanguageKey::INGAME_COMMAND_DESCRIPTION_CLOUD(), "/cloud");
        $this->setPermission("pocketcloud.command.cloud");

        $this->registerSubCommand(SubCommandData::create(
            "help",
            "List all the subcommands",
            fn(CommandSender $sender, string $commandLabel, array $args) => $sender->sendMessage($this->buildUsage())
        ));
    }

    public function registerSubCommand(SubCommandData $subCommandData, bool $override = false): void {
        $name = strtolower($subCommandData->getName());
        if (isset($this->subCommands[$name]) && !$override) return;
        $this->subCommands[$name] = $subCommandData;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        var_dump("hii1");
        if ($sender instanceof Player) {
            var_dump("hii2");
            if ($this->testPermissionSilent($sender)) {
                var_dump("hii3");
                $subCommand = array_shift($args);
                if ($subCommand === null) {
                    $sender->sendForm(new CloudMainForm());
                    return true;
                }

                var_dump("hallo");

                if (!isset($this->subCommands[$subCommand])) {
                    var_dump("sadasdasdas");
                    $sender->sendMessage($this->buildUsage());
                    return true;
                }

                $subCommand = $this->subCommands[$subCommand];
                if (!$subCommand->execute($sender, $commandLabel, $args)) {
                    $sender->sendMessage($subCommand->buildUsage());
                    return true;
                }

                //todo: form & subcommand handling
            } else $sender->sendMessage(LanguageKey::INGAME_NO_PERMISSION());
        }
        return true;
    }

    public function buildUsage(): string {
        $usages = [];
        foreach ($this->subCommands as $subCommand) {
            $usages[] = "§c" . $subCommand->buildUsage();
        }

        return implode("\n", $usages);
    }

    public function generateOverloads(): array {
        $overloads = [];
        foreach ($this->subCommands as $subCommand) $overloads[] = $subCommand->buildOverload();
        return $overloads;
    }

    public static function createCommandsPacket(Player $player): AvailableCommandsPacket {
        $commands = [];
        foreach (Server::getInstance()->getCommandMap()->getCommands() as $command) {
            if (isset($commandData[$command->getName()]) || $command->getName() === "help" || !$command->testPermissionSilent($player)) continue;
            $lname = strtolower($command->getName());
            $aliases = $command->getAliases();
            $aliasObj = null;
            if (count($aliases) > 0) {
                if (!in_array($lname, $aliases, true)) $aliases[] = $lname;
                $aliasObj = new CommandHardEnum(ucfirst($command->getName()) . "Aliases", $aliases);
            }

            $important = false;
            $description = $command->getDescription();

            if ($command instanceof CloudCommand) {
                $overloads = $command->generateOverloads();
                $important = true;
            } else if ($command instanceof VanillaCommand) {
                $description = $description instanceof Translatable ? $player->getLanguage()->translate($description) : $description;

                if (method_exists(AvailableCommandsPacket::class, "convertArg") && method_exists($player->getNetworkSession(), "getProtocolId")) {
                    $overloads = [new CommandOverload(chaining: false, parameters: [CommandParameter::standard("args", AvailableCommandsPacket::convertArg($player->getNetworkSession()->getProtocolId(), AvailableCommandsPacket::ARG_TYPE_RAWTEXT), 0, true)])];
                } else {
                    $overloads = [new CommandOverload(chaining: false, parameters: [CommandParameter::standard("args", AvailableCommandsPacket::ARG_TYPE_RAWTEXT, 0, true)])];
                }
            } else {
                $description = $description instanceof Translatable ? $player->getLanguage()->translate($description) : $description;
                $overloads = [];
            }

            $commands[$command->getName()] = new CommandData(
                $lname,
                $description,
                (int) $important,
                CommandPermissions::NORMAL,
                $aliasObj,
                $overloads,
                []
            );
        }

        return AvailableCommandsPacketAssembler::assemble(array_values($commands), [], []);
    }

    public function getOwningPlugin(): CloudBridge {
        return CloudBridge::getInstance();
    }

    public function getSubCommand(string $name): ?SubCommandData {
        return $this->subCommands[strtolower($name)] ?? null;
    }

    public function getSubCommands(): array {
        return $this->subCommands;
    }
}