<?php

namespace pocketcloud\cloud\bridge\api\provider;

use Closure;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\registry\Registry;
use pocketcloud\cloud\bridge\util\GeneralSettings;
use RuntimeException;

class TemplateProvider {

    public function current(): Template {
        return $this->get(GeneralSettings::getTemplateName()) ?? throw new RuntimeException("Current template shouldn't be null");
    }

    public function pick(Closure $filterClosure): array {
        return array_filter($this->getAll(), $filterClosure);
    }

    public function get(string $name): ?Template {
        return $this->getAll()[$name] ?? null;
    }

    /** @return array<Template> */
    public function getAll(): array {
        return Registry::getTemplates();
    }
}