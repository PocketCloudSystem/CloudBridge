<?php

namespace pocketcloud\cloud\bridge\api\provider;

use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\util\CloudEnvironmentConfig;

final class TemplateProvider implements CloudAPIProvider {
    use CloudAPIGetProviderTrait;

    /** @var array<Template> */
    private array $templates = [];

    public function add(Template $template): void {
        $this->templates[$template->getName()] = $template;
    }

    public function remove(Template $template): void {
        if ($this->isset($template)) unset($this->templates[$template->getName()]);
    }

    public function isset(Template|string $name): bool {
        $name = $name instanceof Template ? $name->getName() : $name;
        return isset($this->templates[$name]);
    }

    public function get(string $name): ?Template {
        return $this->templates[$name] ?? null;
    }

    public function current(): Template {
        return $this->get(CloudEnvironmentConfig::getTemplateName());
    }

    public function getAll(): array {
        return $this->templates;
    }
}