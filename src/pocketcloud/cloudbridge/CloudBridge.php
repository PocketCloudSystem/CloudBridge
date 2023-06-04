<?php

namespace pocketcloud\cloudbridge;

use pmmp\thread\ThreadSafeArray;
use pocketcloud\cloudbridge\api\CloudAPI;
use pocketcloud\cloudbridge\command\CloudCommand;
use pocketcloud\cloudbridge\command\CloudNotifyCommand;
use pocketcloud\cloudbridge\command\TransferCommand;
use pocketcloud\cloudbridge\event\network\NetworkPacketReceiveEvent;
use pocketcloud\cloudbridge\language\Language;
use pocketcloud\cloudbridge\listener\EventListener;
use pocketcloud\cloudbridge\module\npc\listener\NPCListener;
use pocketcloud\cloudbridge\module\sign\listener\SignListener;
use pocketcloud\cloudbridge\network\Network;
use pocketcloud\cloudbridge\network\packet\handler\PacketSerializer;
use pocketcloud\cloudbridge\network\packet\impl\normal\DisconnectPacket;
use pocketcloud\cloudbridge\network\packet\impl\types\DisconnectReason;
use pocketcloud\cloudbridge\network\packet\ResponsePacket;
use pocketcloud\cloudbridge\network\request\RequestManager;
use pocketcloud\cloudbridge\task\TimeoutTask;
use pocketcloud\cloudbridge\util\Address;
use pocketcloud\cloudbridge\util\GeneralSettings;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

class CloudBridge extends PluginBase {
    use SingletonTrait;

    public static function getPrefix(): string {
        return Language::current()->translate("inGame.prefix");
    }

    public array $signDelay = [];
    public array $npcDelay = [];
    public array $npcDetection = [];
    public float|int $lastKeepALiveCheck = 0.0;
    private Network $network;

    protected function onEnable(): void {
        self::setInstance($this);
        if (!file_exists($this->getDataFolder() . "skins/")) mkdir($this->getDataFolder() . "skins/");
        GeneralSettings::sync();
        Language::init();
        $networkBuffer = new ThreadSafeArray();
        $this->network = new Network(new Address("127.0.0.1", GeneralSettings::getNetworkPort()), $this->getServer()->getTickSleeper()->addNotifier(function() use ($networkBuffer): void {
            while (($buffer = $networkBuffer->shift()) !== null) {
                if (($packet = PacketSerializer::decode($buffer)) !== null) {
                    ($ev = new NetworkPacketReceiveEvent($packet))->call();
                    if ($ev->isCancelled()) return;
                    $packet->handle();

                    if ($packet instanceof ResponsePacket) {
                        RequestManager::getInstance()->callThen($packet);
                        RequestManager::getInstance()->removeRequest($packet->getRequestId());
                    }
                } else {
                    \GlobalLogger::get()->warning("§cReceived an unknown packet from the cloud!");
                    \GlobalLogger::get()->debug(GeneralSettings::isNetworkEncryptionEnabled() ? base64_decode($buffer) : $buffer);
                }
            }
        }), $networkBuffer);;
        $this->network->start();

        $this->lastKeepALiveCheck = time();
        $this->getScheduler()->scheduleRepeatingTask(new TimeoutTask(), 20);

        $this->registerPermission("pocketcloud.command.cloud", "pocketcloud.command.notify", "pocketcloud.notify.receive", "pocketcloud.maintenance.bypass", "pocketcloud.command.transfer", "pocketcloud.command.cloudnpc", "pocketcloud.cloudsign.add", "pocketcloud.cloudsign.remove");
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new NPCListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new SignListener(), $this);
        $this->getServer()->getCommandMap()->registerAll("pocketcloud", [
            new CloudNotifyCommand(),
            new CloudCommand(),
            new TransferCommand()
        ]);

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
}