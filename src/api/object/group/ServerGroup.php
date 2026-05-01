<?php

namespace pocketcloud\cloud\bridge\api\object\group;

use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\util\misc\Writeable;
use pocketcloud\cloud\bridge\util\Utils;

final class ServerGroup implements Writeable {

    private array $lowerCaseTemplates;

    public function __construct(
        private readonly string $name,
        private array $templates
    ) {
        $this->lowerCaseTemplates = array_map(fn(string $template) => strtolower($template), $this->templates);
    }

    public static function read(array $data): ?self {
        if (!Utils::containKeys($data, "name", "templates")) return null;
        return new self(
            $data["name"],
            $data["templates"]
        );
    }

    /** @internal */
    public function sync(array $data): void {
        $this->templates = $data["templates"] ?? $this->templates;
        $this->lowerCaseTemplates = array_map(fn(string $template) => strtolower($template), $this->templates);
    }

    public function is(Template|string $template): bool {
        $template = $template instanceof Template ? $template->getName() : $template;
        return in_array($template, $this->templates) || in_array(strtolower($template), $this->lowerCaseTemplates);
    }

    public function getName(): string {
        return $this->name;
    }

    public function write(): array {
        return [
            "name" => $this->name,
            "templates" => $this->templates
        ];
    }

    public function getPlayerCount(): int {
        return count($this->getPlayers());
    }

    /** @return array<CloudPlayer> */
    public function getPlayers(): array {
        return array_filter(CloudPlayerProvider::provider()->getAll(), fn(CloudPlayer $player) => $player->getCurrentProxy()?->getTemplate()?->getParentServerGroup()?->getName() ==
            $this->getName() ||
            $player->getCurrentServer()?->getTemplate()?->getParentServerGroup()?->getName() == $this->getName());
    }

    public function getTemplates(): array {
        return $this->templates;
    }
}