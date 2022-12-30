<?php

namespace pocketcloud\cloudbridge\utils;

use pocketcloud\cloudbridge\config\MessagesConfig;
use pocketcloud\cloudbridge\network\packet\impl\types\TextType;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Message {

    public const CLOUD_COMMAND_DESCRIPTION = "cloud-command-description";
    public const CLOUD_NOTIFY_COMMAND_DESCRIPTION = "cloud-notify-command-description";
    public const TRANSFER_COMMAND_DESCRIPTION = "transfer-command-description";
    public const HUB_COMMAND_DESCRIPTION = "hub-command-description";
    public const CLOUD_NPC_COMMAND_DESCRIPTION = "cloud-npc-command-description";
    public const NO_PERMISSIONS = "no-permissions";
    public const REQUEST_TIMEOUT = "request-timeout";
    public const NOTIFICATIONS_ACTIVATED = "notifications-activated";
    public const NOTIFICATIONS_DEACTIVATED = "notifications-deactivated";
    public const CLOUD_HELP_USAGE = "cloud-help-usage";
    public const CLOUD_LIST_HELP_USAGE = "cloud-list-help-usage";
    public const CLOUD_START_HELP_USAGE = "cloud-start-help-usage";
    public const CLOUD_STOP_HELP_USAGE = "cloud-stop-help-usage";
    public const SERVER_EXISTENCE = "server-existence";
    public const TEMPLATE_EXISTENCE = "template-existence";
    public const MAX_SERVERS = "max-servers";
    public const SERVER_SAVED = "server-saved";
    public const TEMPLATE_MAINTENANCE = "template-maintenance";
    public const CONNECT_TO_SERVER = "connect-to-server";
    public const CONNECT_TO_SERVER_TARGET = "connect-to-server-target";
    public const ALREADY_CONNECTED = "already-connected";
    public const ALREADY_CONNECTED_TARGET = "already-connected-target";
    public const CANT_CONNECT = "cant-connect";
    public const CANT_CONNECT_TARGET = "cant-connect-target";
    public const NPC_NAME_TAG = "npc-name-tag";
    public const PROCESS_CANCELLED = "process-cancelled";
    public const SELECT_NPC = "select-npc";
    public const ALREADY_NPC = "already-npc";
    public const NPC_CREATED = "npc-created";
    public const NPC_REMOVED = "npc-removed";
    public const NO_SERVER_FOUND = "no-server-found";
    public const ALREADY_IN_LOBBY = "already-in-lobby";
    public const TRANSFER_HELP_USAGE = "transfer-help-usage";
    public const UI_NPC_CHOOSE_SERVER_TITLE = "ui-npc-choose-server-title";
    public const UI_NPC_CHOOSE_SERVER_TEXT = "ui-npc-choose-server-text";
    public const UI_NPC_CHOOSE_SERVER_BUTTON = "ui-npc-choose-server-button";
    public const UI_NPC_CHOOSE_SERVER_NO_SERVER = "ui-npc-choose-server-no-server";

    public static function parse(string $key, array $replacements = []): self {
        $same = false;
        $message = str_replace("{PREFIX}", MessagesConfig::getInstance()->getPrefix(), MessagesConfig::getInstance()->getConfig()->get($key, $key));
        if ($message == $key) $same = true;
        if ($same) $message .= " {";
        foreach ($replacements as $index => $replacement) {
            $message = str_replace("%" . $index . "%", $replacement, $message);
            if ($same) {
                $message .= "\n  %" . $index . "%: " . $replacement . ($index == (count($replacements) - 1) ? "\n" : "");
            }
        }
        if ($same) $message .= "}";
        return new self($message);
    }

    public function __construct(private string $message) {}

    public function target(CommandSender $sender, ?TextType $type = null): self {
        if ($type === null || $type === TextType::MESSAGE()) {
            $sender->sendMessage($this->message);
        } else if ($sender instanceof Player) {
            if ($type === TextType::TITLE()) $sender->sendTitle($this->message);
            else if ($type === TextType::POPUP()) $sender->sendPopup($this->message);
            else if ($type === TextType::TIP()) $sender->sendTip($this->message);
            else if ($type === TextType::ACTION_BAR()) $sender->sendActionBarMessage($this->message);
        }
        return $this;
    }

    public function getMessage(): string {
        return $this->message;
    }

    public function __toString(): string {
        return $this->message;
    }
}