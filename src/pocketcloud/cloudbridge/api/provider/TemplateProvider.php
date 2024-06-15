<?php

namespace pocketcloud\cloudbridge\api\provider;

use Closure;
use pocketcloud\cloudbridge\api\object\template\Template;
use pocketcloud\cloudbridge\api\registry\Registry;
use pocketcloud\cloudbridge\util\GeneralSettings;
use RuntimeException;

class TemplateProvider {

    public function current(): Template {
        return $this->getTemplate(GeneralSettings::getTemplateName()) ?? throw new RuntimeException("Current template shouldn't be null");
    }

    public function pickTemplates(Closure $filterClosure): array {
        return array_filter($this->getTemplates(), $filterClosure);
    }

    public function getTemplate(string $name): ?Template {
        return $this->getTemplates()[$name] ?? null;
    }

    /** @return array<Template> */
    public function getTemplates(): array {
        return Registry::getTemplates();
    }
}