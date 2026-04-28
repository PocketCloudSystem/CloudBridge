<?php

namespace pocketcloud\cloud\bridge\api\provider;

use Closure;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\command\util\ParameterType;
use pocketcloud\cloud\bridge\util\CloudEnvironmentConfig;
use RuntimeException;

final class TemplateProvider implements CloudAPIProvider {
    use CloudAPIGetProviderTrait;

    /** @var array<Template> */
    private array $templates = [];

    public function add(Template $template): void {
        if ($this->isset($template)) {
            $this->templates[strtolower($template->getName())]->sync($template->write());
        } else {
            $this->templates[strtolower($template->getName())] = $template;
            ParameterType::updateEnum(ParameterType::TEMPLATE);
        }
    }

    public function addAll(Template ...$templates): void {
        foreach ($templates as $template) {
            if ($this->isset($template)) {
                $this->templates[strtolower($template->getName())]->sync($template->write());
            } else {
                $this->templates[strtolower($template->getName())] = $template;
            }
        }

        ParameterType::updateEnum(ParameterType::TEMPLATE);
    }

    public function isset(Template|string $name): bool {
        $name = $name instanceof Template ? $name->getName() : $name;
        return isset($this->templates[strtolower($name)]);
    }

    public function remove(Template $template): void {
        if ($this->isset($template)) {
            unset($this->templates[strtolower($template->getName())]);
            ParameterType::updateEnum(ParameterType::TEMPLATE);
        }
    }

    public function current(): Template {
        return $this->get(CloudEnvironmentConfig::getTemplateName()) ?? throw new RuntimeException("The return value of current() should not be null, wait for CloudAPI to index");
    }

    public function get(string $name): ?Template {
        return $this->templates[strtolower($name)] ?? null;
    }

    public function pick(Closure $filter): array {
        return array_filter($this->templates, $filter);
    }

    public function getAll(): array {
        return $this->templates;
    }
}