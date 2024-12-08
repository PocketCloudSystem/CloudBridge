<?php

namespace pocketcloud\cloud\bridge;

use GlobalLogger;
use pmmp\thread\ThreadSafeArray;
use pocketcloud\cloud\bridge\api\CloudAPI;
use pocketcloud\cloud\bridge\command\CloudCommand;
use pocketcloud\cloud\bridge\command\CloudNotifyCommand;
use pocketcloud\cloud\bridge\command\TransferCommand;
use pocketcloud\cloud\bridge\event\network\NetworkPacketReceiveEvent;
use pocketcloud\cloud\bridge\language\Language;
use pocketcloud\cloud\bridge\listener\EventListener;
use pocketcloud\cloud\bridge\module\npc\listener\NPCListener;
use pocketcloud\cloud\bridge\module\sign\listener\SignListener;
use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\network\packet\handler\PacketSerializer;
use pocketcloud\cloud\bridge\network\packet\impl\normal\DisconnectPacket;
use pocketcloud\cloud\bridge\network\packet\impl\type\DisconnectReason;
use pocketcloud\cloud\bridge\network\packet\ResponsePacket;
use pocketcloud\cloud\bridge\network\request\RequestManager;
use pocketcloud\cloud\bridge\task\TimeoutTask;
use pocketcloud\cloud\bridge\util\GeneralSettings;
use pocketcloud\cloud\bridge\util\net\Address;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

final class CloudBridge extends PluginBase {
    use SingletonTrait;

    public static function getPrefix(): string {
        return Language::current()->translate("inGame.prefix");
    }

    public array $signDelay = [];
    public float|int $lastKeepALiveCheck = 0.0;
    private Network $network;

    protected function onEnable(): void {
        self::setInstance($this);
        if (!file_exists($this->getDataFolder() . "skins/")) mkdir($this->getDataFolder() . "skins/");
        GeneralSettings::sync();

        $networkBuffer = new ThreadSafeArray();
        $this->network = new Network(new Address("127.0.0.1", GeneralSettings::getNetworkPort()), $this->getServer()->getTickSleeper()->addNotifier(function() use ($networkBuffer): void {
            while (($buffer = $networkBuffer->shift()) !== null) {
                if (($packet = PacketSerializer::decode($buffer)) !== null) {
                    ($ev = new NetworkPacketReceiveEvent($packet))->call();
                    if ($ev->isCancelled()) return;
                    $packet->handle();

                    if ($packet instanceof ResponsePacket) {
                        RequestManager::getInstance()->resolve($packet);
                        RequestManager::getInstance()->remove($packet->getRequestId());
                    }
                } else {
                    GlobalLogger::get()->warning("§cReceived an unknown packet from the cloud!");
                    GlobalLogger::get()->debug(GeneralSettings::isNetworkEncryptionEnabled() ? base64_decode($buffer) : $buffer);
                }
            }
        }), $networkBuffer);
        $this->network->start();

        $this->lastKeepALiveCheck = time();
        $this->getScheduler()->scheduleRepeatingTask(new TimeoutTask(), 20);

        $this->registerPermission("pocketcloud.command.cloud", "pocketcloud.command.notify", "pocketcloud.notify.receive", "pocketcloud.maintenance.bypass", "pocketcloud.command.transfer", "pocketcloud.command.cloudnpc", "pocketcloud.command.template_group", "pocketcloud.cloudsign.add", "pocketcloud.cloudsign.remove");
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new NPCListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new SignListener(), $this);
        $this->getServer()->getCommandMap()->registerAll("cloudBridge", [
            new CloudCommand(),
            new TransferCommand(),
            new CloudNotifyCommand()
        ]);

        CloudAPI::get()->processLogin();
    }

    public function registerPermission(string... $permissions): void {
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