<?php

namespace pocketcloud\cloud\bridge\player;

use pocketcloud\cloud\bridge\command\util\ParameterType;
use pocketmine\network\mcpe\protocol\UpdateSoftEnumPacket;
use pocketmine\player\Player;
use pocketmine\Server;

final class PlayerSession {

    public const int DEFAULT_INTERACTION_COOLDOWN = 10;

    private ?int $npcInteractionCooldown = null;
    private bool $awaitNpcRemoval = false;
    private ?int $awaitNpcRemovalTimeoutTick = null;

    private ?int $signInteractionCooldown = null;

    public function __construct(private readonly string $name) {
        $player = $this->getPlayer();
        if ($player !== null) {
            $pks = [];
            foreach (ParameterType::cases() as $type) {
                if ($type->isSoftEnum()) {
                    $pks[] = UpdateSoftEnumPacket::create($type->getEnumName(), $type->generateEnumContent(), UpdateSoftEnumPacket::TYPE_ADD);
                }
            }

            if (!empty($pks)) {
                foreach ($pks as $pk) {
                    $player->getNetworkSession()->sendDataPacket($pk);
                }
            }
        }
    }

    public function tick(): void {
        if ($this->npcInteractionCooldown !== null && $this->npcInteractionCooldown <= Server::getInstance()->getTick()) {
            $this->npcInteractionCooldown = null;
        }

        if ($this->awaitNpcRemoval && $this->awaitNpcRemovalTimeoutTick <= Server::getInstance()->getTick()) {
            $this->awaitNpcRemoval = true;
            $this->awaitNpcRemovalTimeoutTick = null;
        }

        if ($this->signInteractionCooldown !== null && $this->signInteractionCooldown <= Server::getInstance()->getTick()) {
            $this->signInteractionCooldown = null;
        }
    }

    public function setAwaitNpcRemoval(bool $awaitNpcRemoval): PlayerSession {
        $this->awaitNpcRemoval = $awaitNpcRemoval;
        if ($awaitNpcRemoval) $this->awaitNpcRemovalTimeoutTick = Server::getInstance()->getTick();
        else $this->awaitNpcRemovalTimeoutTick = null;
        return $this;
    }

    public function setOnNpcInteractionCooldown(): PlayerSession {
        $this->npcInteractionCooldown = Server::getInstance()->getTick() + self::DEFAULT_INTERACTION_COOLDOWN;
        return $this;
    }

    public function setOnSignInteractionCooldown(): PlayerSession {
        $this->signInteractionCooldown = Server::getInstance()->getTick() + self::DEFAULT_INTERACTION_COOLDOWN;
        return $this;
    }

    public function getPlayer(): ?Player {
        return Server::getInstance()->getPlayerExact($this->name);
    }

    public function getPlayerName(): string {
        return $this->name;
    }

    public function isOnNpcInteractionCooldown(): bool {
        return $this->npcInteractionCooldown !== null;
    }

    public function isOnSignInteractionCooldown(): bool {
        return $this->signInteractionCooldown !== null;
    }

    public function isAwaitNpcRemoval(): bool {
        return $this->awaitNpcRemoval;
    }

    public function getNpcInteractionCooldown(): int {
        return $this->npcInteractionCooldown;
    }

    public static function get(Player $player): PlayerSession {
        return PlayerSessionManager::getInstance()->get($player);
    }
}