<?php

namespace pocketcloud\cloud\bridge\network\packet\impl;

use pocketcloud\cloud\bridge\api\object\group\ServerGroup;
use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\api\provider\ServerGroupProvider;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\network\packet\ClientboundPacket;
use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;

final class BulkSyncPacket extends CloudPacket implements ClientboundPacket {

    public function __construct(
        private ?array $servers = [],
        private ?array $templates = [],
        private ?array $players = [],
        private ?array $groups = []
    ) {}

    public static function create(array $servers, array $templates, array $players, array $groups): self {
        return new self($servers, $templates, $players, $groups);
    }

    public function handle(): void {
        CloudServerProvider::provider()->addAll(...$this->servers);
        TemplateProvider::provider()->addAll(...$this->templates);
        CloudPlayerProvider::provider()->addAll(...$this->players);
        ServerGroupProvider::provider()->addAll(...$this->groups);
    }

    public function encodePayload(PacketData $packetData): void {}

    public function decodePayload(PacketData $packetData): void {
        $servers = [];
        $templates = [];
        $players = [];
        $groups = [];

        foreach ($packetData->readArray() as $server) {
            if (is_array($server) && ($server = CloudServer::read($server)) !== null) {
                $servers[] = $server;
            }
        }

        foreach ($packetData->readArray() as $template) {
            if (is_array($template) && ($template = Template::read($template)) !== null) {
                $templates[] = $template;
            }
        }

        foreach ($packetData->readArray() as $player) {
            if (is_array($player) && ($player = CloudPlayer::read($player)) !== null) {
                $players[] = $player;
            }
        }

        foreach ($packetData->readArray() as $group) {
            if (is_array($group) && ($group = ServerGroup::read($group)) !== null) {
                $groups[] = $group;
            }
        }

        $this->servers = $servers;
        $this->templates = $templates;
        $this->players = $players;
        $this->groups = $groups;
    }
}