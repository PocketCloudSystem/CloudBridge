<?php

namespace pocketcloud\cloud\bridge\api\object\group;

use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\util\misc\Writeable;
use pocketcloud\cloud\bridge\util\Utils;

final class ServerGroup implements Writeable {

    public function __construct(
        private readonly string $name,
        private array $templates
    ) {}

    /** @internal */
    public function sync(array $data): void {
        $this->templates = $data["templates"] ?? $this->templates;
    }

    public function is(Template|string $template): bool {
        $template = $template instanceof Template ? $template->getName() : $template;
        return in_array($template, $this->templates);
    }

    public function write(): array {
        return [
            "name" => $this->name,
            "templates" => $this->templates
        ];
    }

    public function getName(): string {
        return $this->name;
    }

    public function getTemplates(): array {
        return $this->templates;
    }

    public static function read(array $data): ?self {
        if (!Utils::containKeys($data, "name", "templates")) return null;
        return new self(
            $data["name"],
            $data["templates"]
        );
    }
}