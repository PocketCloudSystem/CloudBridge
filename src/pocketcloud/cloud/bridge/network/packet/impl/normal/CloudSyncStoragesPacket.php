<?php

namespace pocketcloud\cloud\bridge\network\packet\impl\normal;

use pocketcloud\cloud\bridge\api\CloudAPI;

use pocketcloud\cloud\bridge\network\packet\CloudPacket;
use pocketcloud\cloud\bridge\network\packet\data\PacketData;

//coming from the cloud
class CloudSyncStoragesPacket extends CloudPacket {

    private array $storage = [];

    public function __construct() {}

    public function encodePayload(PacketData $packetData): void {
        $packetData->write($this->storage);
    }

    public function decodePayload(PacketData $packetData): void {
        $this->storage = $packetData->readArray();
    }

    public function getStorage(): array {
        return $this->storage;
    }

    public function handle(): void {
        foreach ($this->storage as $server => $data) {
            if (($server = CloudAPI::servers()->get($server)) !== null) {
                $server->getCloudServerStorage()->sync($data);
            }
        }
    }
}