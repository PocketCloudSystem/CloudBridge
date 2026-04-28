<?php

namespace pocketcloud\cloud\bridge\api\provider;

use pocketcloud\cloud\bridge\api\object\group\ServerGroup;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\command\util\ParameterType;
use pocketcloud\cloud\bridge\util\CloudEnvironmentConfig;

final class ServerGroupProvider implements CloudAPIProvider {
    use CloudAPIGetProviderTrait;

    /** @var array<ServerGroup> */
    private array $serverGroups = [];

    public function add(ServerGroup $serverGroup): void {
        if ($this->isset($serverGroup)) {
            $this->serverGroups[strtolower($serverGroup->getName())]->sync($serverGroup->write());
        } else {
            $this->serverGroups[strtolower($serverGroup->getName())] = $serverGroup;
        }
    }

    public function addAll(ServerGroup ...$serverGroups): void {
        foreach ($serverGroups as $serverGroup) {
            if ($this->isset($serverGroup)) {
                $this->serverGroups[strtolower($serverGroup->getName())]->sync($serverGroup->write());
            } else {
                $this->serverGroups[strtolower($serverGroup->getName())] = $serverGroup;
            }
        }

        ParameterType::updateEnum(ParameterType::GROUP);
    }

    public function isset(ServerGroup|string $name): bool {
        $name = $name instanceof ServerGroup ? $name->getName() : $name;
        return isset($this->serverGroups[strtolower($name)]);
    }

    public function remove(ServerGroup $serverGroup): void {
        if ($this->isset($serverGroup)) {
            unset($this->serverGroups[strtolower($serverGroup->getName())]);
            ParameterType::updateEnum(ParameterType::GROUP);
        }
    }

    public function current(): ?ServerGroup {
        return $this->get(CloudEnvironmentConfig::getTemplateName());
    }

    public function get(Template|string $name): ?ServerGroup {
        $name = $name instanceof Template ? $name->getName() : $name;
        if (isset($this->serverGroups[strtolower($name)])) return $this->serverGroups[strtolower($name)];
        return array_find($this->serverGroups, fn(ServerGroup $group) => $group->is($name));
    }

    public function getAll(): array {
        return $this->serverGroups;
    }
}