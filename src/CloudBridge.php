<?php

namespace pocketcloud\cloud\bridge;

use pocketcloud\cloud\bridge\network\Network;
use pocketcloud\cloud\bridge\task\RequestTimeoutTask;
use pocketcloud\cloud\bridge\util\CloudEnvironmentConfig;
use pocketcloud\cloud\bridge\util\net\Address;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

final class CloudBridge extends PluginBase {
    use SingletonTrait;

    private Network $network;

    protected function onLoad(): void {
        self::setInstance($this);
        CloudEnvironmentConfig::sync();

        $this->network = new Network(Address::create(CloudEnvironmentConfig::getNetworkAddress(), CloudEnvironmentConfig::getNetworkPort()));
    }

    protected function onEnable(): void {
        $this->network->init();
        $this->network->start();
        $this->getScheduler()->scheduleRepeatingTask(new RequestTimeoutTask(), 20);
    }

    public function registerPermission(string... $permissions): void {
        $operator = PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_OPERATOR);
        if ($operator !== null) {
            foreach ($permissions as $permission) {
                DefaultPermissions::registerPermission(new Permission($permission), [$operator]);
            }
        }
    }

    public static function getInstance(): self {
        return self::$instance;
    }
}