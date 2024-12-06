<?php

namespace pocketcloud\cloud\bridge\module\npc\group;

use pocketcloud\cloud\bridge\util\Utils;

class TemplateGroup {

    public function __construct(
        private readonly string $id,
        private string $displayName,
        private array $templates
    ) {}

    public function applyData(array $data): void {
        $this->displayName = $data["display_name"];
        $this->templates = $data["templates"];
    }

    public function addTemplate(string ...$templates): void {
        foreach ($templates as $template) {
            $this->templates[] = $template;
        }

        $this->templates = array_values($this->templates);
    }

    public function removeTemplate(string $template): void {
        if ($this->containsTemplate($template)) {
            unset($this->templates[array_search($template, $this->templates)]);
            $this->templates = array_values($this->templates);
        }
    }

    public function containsTemplate(string $template): bool {
        return in_array($template, $this->templates);
    }

    public function setDisplayName(string $displayName): void {
        $this->displayName = $displayName;
    }

    public function getId(): string {
        return $this->id;
    }

    public function getDisplayName(): string {
        return $this->displayName;
    }

    public function getTemplates(): array {
        return $this->templates;
    }

    public function toArray(): array {
        return [
            "id" => $this->id,
            "display_name" => $this->displayName,
            "templates" => $this->templates
        ];
    }

    public static function fromArray(array $data): ?TemplateGroup {
        if (!Utils::containKeys($data, "id", "display_name", "templates")) return null;
        if (!is_array($data["templates"])) return null;
        return new TemplateGroup($data["id"], $data["display_name"], $data["templates"]);
    }
}