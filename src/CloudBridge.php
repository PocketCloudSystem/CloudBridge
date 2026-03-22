<?php

namespace pocketcloud\cloud\bridge;

use pocketcloud\cloud\bridge\api\CloudAPI;
use pocketcloud\cloud\bridge\command\CloudCommand;
use pocketcloud\cloud\bridge\command\CloudNotifyCommand;
use pocketcloud\cloud\bridge\command\TransferCommand;
use pocketcloud\cloud\bridge\listener\EventListener;
use pocketcloud\cloud\bridge\module\ModuleManager;
use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\network\packet\data\ServerDisconnectReason;
use pocketcloud\cloud\bridge\network\packet\impl\DisconnectPacket;
use pocketcloud\cloud\bridge\player\PlayerSessionManager;
use pocketcloud\cloud\bridge\task\RequestTimeoutTask;
use pocketcloud\cloud\bridge\task\ServerTimeoutTask;
use pocketcloud\cloud\bridge\task\StatusChangeTask;
use pocketcloud\cloud\bridge\traffic\TrafficMonitorManager;
use pocketcloud\cloud\bridge\util\CloudEnvironmentConfig;
use pocketcloud\cloud\bridge\util\loader\LibraryClassLoader;
use pocketcloud\cloud\bridge\util\net\Address;
use pocketcloud\cloud\bridge\util\ProcessUtils;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

final class CloudBridge extends PluginBase {
    use SingletonTrait;

    private int $lastAliveCheck = 0;

    private LibraryClassLoader $libraryClassLoader;
    private CloudAPI $cloudAPI;
    private Network $network;
    private PlayerSessionManager $sessionManager;
    private ModuleManager $moduleManager;
    private TrafficMonitorManager $trafficMonitorManager;

    public function registerCommands(): void {
        $this->getServer()->getCommandMap()->registerAll("cloudBridge", [
            new CloudNotifyCommand(),
            new CloudCommand(),
            new TransferCommand()
        ]);

        ModuleManager::getInstance()->load();
    }

    protected function onLoad(): void {
        self::setInstance($this);
        if (!is_dir($this->getDataFolder() . "skins")) mkdir($this->getDataFolder() . "skins");
        $this->libraryClassLoader = new LibraryClassLoader();
        CloudEnvironmentConfig::sync();

        $this->cloudAPI = new CloudAPI();
        $this->network = new Network(Address::create(CloudEnvironmentConfig::getNetworkAddress(), CloudEnvironmentConfig::getNetworkPort()));
    }

    protected function onEnable(): void {
        $this->libraryClassLoader->init();
        $this->network->init();
        $this->network->start();

        $this->sessionManager = new PlayerSessionManager();
        $this->moduleManager = new ModuleManager();
        $this->trafficMonitorManager = new TrafficMonitorManager();

        $this->getScheduler()->scheduleRepeatingTask(new RequestTimeoutTask(), 20);
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->registerPermission("pocketcloud.command.notify", "pocketcloud.command.cloud", "pocketcloud.bypass.maintenance");
        $this->registerPermission("pocketcloud.command.hub", "pocketcloud.command.transfer");
        $this->registerPermission("pocketcloud.command.cloudnpc", "pocketcloud.command.template_group");
        $this->registerPermission("pocketcloud.cloudsign.add", "pocketcloud.cloudsign.remove");

        ProcessUtils::startCpuRetrieveCycle();
        $this->getScheduler()->scheduleDelayedRepeatingTask(new ClosureTask(function (): void {
            ProcessUtils::restartCpuRetrieveCycle();
        }), 40, 40);

        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            TrafficMonitorManager::getInstance()->tick(Server::getInstance()->getTick());
            PlayerSessionManager::getInstance()->tick();
            ModuleManager::getInstance()->tick();
        }), 1);

        $this->cloudAPI->requestLogin();
    }

    protected function onDisable(): void {
        $this->network->sendPacket(DisconnectPacket::create(ServerDisconnectReason::SERVER_SHUTDOWN));
        $this->network->close();

        Server::getInstance()->shutdown();
    }

    public function registerPermission(string...$permissions): void {
        $operator = PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_OPERATOR);
        if ($operator !== null) {
            foreach ($permissions as $permission) {
                DefaultPermissions::registerPermission(new Permission($permission), [$operator]);
            }
        }
    }

    public function startTasks(): void {
        $this->getScheduler()->scheduleRepeatingTask(new ServerTimeoutTask(), 20);
        $this->getScheduler()->scheduleRepeatingTask(new StatusChangeTask(), 20);
    }

    public function setLastAliveCheck(int $lastAliveCheck): void {
        $this->lastAliveCheck = $lastAliveCheck;
    }

    public function getLastAliveCheck(): int {
        return $this->lastAliveCheck;
    }

    public function getLibraryClassLoader(): LibraryClassLoader {
        return $this->libraryClassLoader;
    }

    public function getCloudAPI(): CloudAPI {
        return $this->cloudAPI;
    }

    public function getNetwork(): Network {
        return $this->network;
    }

    public function getSessionManager(): PlayerSessionManager {
        return $this->sessionManager;
    }

    public function getModuleManager(): ModuleManager {
        return $this->moduleManager;
    }

    public function getTrafficMonitorManager(): TrafficMonitorManager {
        return $this->trafficMonitorManager;
    }

    public static function getInstance(): self {
        return self::$instance;
    }
}