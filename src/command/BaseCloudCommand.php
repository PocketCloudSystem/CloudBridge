<?php

namespace pocketcloud\cloud\bridge\command;

use pocketcloud\cloud\bridge\CloudBridge;
use pocketcloud\cloud\bridge\command\util\CommandParameterTrait;
use pocketcloud\cloud\bridge\command\util\SubCommandData;
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
use pocketmine\plugin\PluginOwned;
use pocketmine\Server;
use Throwable;

abstract class BaseCloudCommand extends Command implements PluginOwned {
    use CommandParameterTrait;

    /** @var array<string, SubCommandData> */
    private array $subCommands = [];

    final public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermissionSilent($sender)) {
            $sender->sendMessage(LanguageKey::INGAME_NO_PERMISSION()->translate());
            return true;
        }

        $subCommand = null;
        if (!empty($this->subCommands)) {
            if ($this->mustUseSubCommands() && count($args) < 1) {
                $sender->sendMessage($this->buildUsage());
                return true;
            }

            if (count($args) > 0) $subCommand = $this->subCommands[$args[0]] ?? null;
            if ($subCommand === null && $this->mustUseSubCommands()) {
                $sender->sendMessage($this->buildUsage());
                return true;
            } else if ($subCommand instanceof SubCommandData) array_shift($args);
        }

        try {
            if ($subCommand === null) $parsedArgs = $this->parseArgs($args, $currentParameter);
            else $parsedArgs = $subCommand->parseArgs($args, $currentParameter);
        } catch (Throwable $e) {
            CloudBridge::getInstance()->getLogger()->logException($e);
            return true;
        }

        if (!is_array($parsedArgs)) {
            if ($currentParameter?->getType()?->getErrorMessage() !== null) {
                $sender->sendMessage(LanguageKey::INGAME_PREFIX() . $currentParameter->getType()->getErrorMessage());
                return true;
            }

            $sender->sendMessage($subCommand?->buildUsage() ?? $this->buildUsage());
            return true;
        }

        if ($subCommand === null) {
            if (!$this->run($sender, $commandLabel, $parsedArgs)) {
                $sender->sendMessage($this->buildUsage());
            }
        } else {
            if (!$subCommand->execute($sender, $commandLabel, $parsedArgs)) {
                $sender->sendMessage($subCommand->buildUsage());
            }
        }

        return true;
    }

    abstract public function run(CommandSender $sender, string $commandLabel, array $args): bool;

    public function registerSubCommand(SubCommandData $subCommandData, bool $override = false): void {
        $name = strtolower($subCommandData->getName());
        if (isset($this->subCommands[$name]) && !$override) return;
        $this->subCommands[$name] = $subCommandData;
    }

    final public function buildUsage(): string {
        $usages = [];
        foreach ($this->subCommands as $subCommand) {
            $usages[] = "§c" . $subCommand->buildUsage();
        }

        if (!empty($this->parameters)) {
            $finalUsage = LanguageKey::INGAME_PREFIX() . "§c/cloud";
            foreach ($this->parameters as $parameter) {
                $finalUsage .= " " .
                    ($parameter->isOptional() ? "[" : "<") .
                    $parameter->getName() .
                    ": " .
                    strtolower($parameter->getType()->name) .
                    ($parameter->isOptional() ? "]" : ">");
            }

            $usages[] = $finalUsage;
        }

        return implode("\n", $usages);
    }

    public function generateOverloads(): array {
        $overloads = [];
        foreach ($this->subCommands as $subCommand) $overloads[] = $subCommand->buildOverload();

        $params = [];
        foreach ($this->parameters as $parameter) $params[] = $parameter->buildCommandParameter();

        if (!empty($params)) $overloads = array_merge([new CommandOverload(false, $params)], $overloads);

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

            if ($command instanceof BaseCloudCommand) {
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

    public function mustUseSubCommands(): int {
        return count($this->subCommands) > 0 && count($this->parameters) == 0 && count(array_filter($this->subCommands, fn(SubCommandData $subCommand) => $subCommand->isOptional())) !== count($this->subCommands);
    }

    public function getSubCommands(): array {
        return $this->subCommands;
    }

    final public function getOwningPlugin(): CloudBridge {
        return CloudBridge::getInstance();
    }
}