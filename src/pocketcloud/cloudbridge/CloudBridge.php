<?php

namespace pocketcloud\cloudbridge;

use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\command\CloudCommand;
use pocketcloud\cloudbridge\command\CloudNotifyCommand;
use pocketcloud\cloudbridge\command\TransferCommand;
use pocketcloud\cloudbridge\config\MessagesConfig;
use pocketcloud\cloudbridge\config\ModulesConfig;
use pocketcloud\cloudbridge\config\SignLayoutConfig;
use pocketcloud\cloudbridge\listener\EventListener;
use pocketcloud\cloudbridge\module\globalchat\GlobalChat;
use pocketcloud\cloudbridge\module\hubcommand\HubCommand;
use pocketcloud\cloudbridge\module\npc\CloudNPCManager;
use pocketcloud\cloudbridge\module\sign\CloudSignManager;
use pocketcloud\cloudbridge\network\Network;
use pocketcloud\cloudbridge\network\packet\handler\PacketHandler;
use pocketcloud\cloudbridge\network\packet\impl\normal\DisconnectPacket;
use pocketcloud\cloudbridge\network\packet\impl\types\DisconnectReason;
use pocketcloud\cloudbridge\task\TimeoutTask;
use pocketcloud\cloudbridge\utils\Address;
use pocketcloud\cloudbridge\utils\Message;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\plugin\PluginBase;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\utils\SingletonTrait;

class CloudBridge extends PluginBase {
    use SingletonTrait;

    public static function getPrefix(): string {
        return MessagesConfig::getInstance()->getPrefix();
    }

    private MessagesConfig $messagesConfig;
    private ModulesConfig $modulesConfig;
    private SignLayoutConfig $signLayoutConfig;
    private Network $network;
    private CloudSignManager $cloudSignManager;
    private CloudNPCManager $cloudNPCManager;
    private GlobalChat $globalChat;
    private HubCommand $hubCommand;
    public array $signDelay = [];
    public array $npcDelay = [];
    public array $npcDetection = [];
    public float|int $lastKeepALiveCheck = 0.0;

    protected function onEnable(): void {
        if (!file_exists($this->getDataFolder() . "skins/")) @mkdir($this->getDataFolder() . "skins/");
        self::setInstance($this);

        $this->modulesConfig = new ModulesConfig();
        $this->messagesConfig = new MessagesConfig();
        $this->signLayoutConfig = new SignLayoutConfig();
        $this->network = new Network(new Address("127.0.0.1", CloudAPI::getInstance()->getCloudPort()), $networkNotifier = new SleeperNotifier(), $networkBuffer = new \ThreadedArray());
        $this->getServer()->getTickSleeper()->addNotifier($networkNotifier, function() use ($networkBuffer): void {
            while (($buffer = $networkBuffer->shift()) !== null) PacketHandler::getInstance()->handle($buffer);
        });
        $this->network->start();

        $this->registerPermission("pocketcloud.command.cloud", "pocketcloud.command.notify", "pocketcloud.notify.receive", "pocketcloud.maintenance.bypass", "pocketcloud.command.transfer");
        $this->getServer()->getCommandMap()->registerAll("pocketCloud", [
            new CloudNotifyCommand("cloudnotify", Message::parse(Message::CLOUD_NOTIFY_COMMAND_DESCRIPTION)),
            new CloudCommand("cloud", Message::parse(Message::CLOUD_COMMAND_DESCRIPTION)),
            new TransferCommand("transfer", Message::parse(Message::TRANSFER_COMMAND_DESCRIPTION))
        ]);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);

        $this->cloudSignManager = new CloudSignManager();
        $this->cloudNPCManager = new CloudNPCManager();
        $this->globalChat = new GlobalChat();
        $this->hubCommand = new HubCommand();

        $this->lastKeepALiveCheck = microtime(true);
        $this->getScheduler()->scheduleRepeatingTask(new TimeoutTask(), 20);

        CloudAPI::getInstance()->processLogin();
    }

    public function registerPermission(string... $permissions) {
        $operator = PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_OPERATOR);
        if ($operator !== null) {
            foreach ($permissions as $permission) {
                DefaultPermissions::registerPermission(new Permission($permission), [$operator]);
            }
        }
    }

    protected function onDisable(): void {
        $this->network->sendPacket(new DisconnectPacket(DisconnectReason::SERVER_SHUTDOWN()));
        $this->network->close();
    }

    public function getHubCommand(): HubCommand {
        return $this->hubCommand;
    }

    public function getGlobalChat(): GlobalChat {
        return $this->globalChat;
    }

    public function getCloudNPCManager(): CloudNPCManager {
        return $this->cloudNPCManager;
    }

    public function getCloudSignManager(): CloudSignManager {
        return $this->cloudSignManager;
    }

    public function getNetwork(): Network {
        return $this->network;
    }

    public function getSignLayoutConfig(): SignLayoutConfig {
        return $this->signLayoutConfig;
    }

    public function getModulesConfig(): ModulesConfig {
        return $this->modulesConfig;
    }


    public function getMessagesConfig(): MessagesConfig {
        return $this->messagesConfig;
    }
}