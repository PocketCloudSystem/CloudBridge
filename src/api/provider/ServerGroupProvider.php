<?php

namespace pocketcloud\cloud\bridge\api\provider;

use pocketcloud\cloud\bridge\api\object\group\ServerGroup;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\util\CloudEnvironmentConfig;

final class ServerGroupProvider implements CloudAPIProvider {
    use CloudAPIGetProviderTrait;

    /** @var array<ServerGroup> */
    private array $serverGroups = [];

    public function add(ServerGroup $serverGroup): void {
        if ($this->isset($serverGroup)) $this->serverGroups[$serverGroup->getName()]->sync($serverGroup->write());
        else $this->serverGroups[$serverGroup->getName()] = $serverGroup;
    }

    public function isset(ServerGroup|string $name): bool {
        $name = $name instanceof ServerGroup ? $name->getName() : $name;
        return isset($this->serverGroups[$name]);
    }

    public function remove(ServerGroup $serverGroup): void {
        if ($this->isset($serverGroup)) unset($this->serverGroups[$serverGroup->getName()]);
    }

    public function current(): ?ServerGroup {
        return $this->get(CloudEnvironmentConfig::getTemplateName());
    }

    public function get(Template|string $name): ?ServerGroup {
        $name = $name instanceof Template ? $name->getName() : $name;
        if (isset($this->serverGroups[$name])) return $this->serverGroups[$name];
        return array_find($this->serverGroups, fn(ServerGroup $group) => $group->is($name));
    }

    public function getAll(): array {
        return $this->serverGroups;
    }
}