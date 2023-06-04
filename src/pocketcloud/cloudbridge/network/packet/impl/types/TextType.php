<?php

namespace pocketcloud\cloudbridge\network\packet\impl\types;

use pocketmine\utils\RegistryTrait;

/**
 * @method static TextType MESSAGE()
 * @method static TextType POPUP()
 * @method static TextType TIP()
 * @method static TextType TITLE()
 * @method static TextType ACTION_BAR()
 * @method static TextType TOAST_NOTIFICATION()
 */
final class TextType {
    use RegistryTrait;

    protected static function setup(): void {
        self::_registryRegister("message", new TextType("MESSAGE"));
        self::_registryRegister("popup", new TextType("POPUP"));
        self::_registryRegister("tip", new TextType("TIP"));
        self::_registryRegister("title", new TextType("TITLE"));
        self::_registryRegister("action_bar", new TextType("ACTION_BAR"));
        self::_registryRegister("toast_notification", new TextType("TOAST_NOTIFICATION"));
    }

    public static function getTypeByName(string $name): ?TextType {
        self::checkInit();
        return self::$members[strtoupper($name)] ?? null;
    }

    /** @return array<TextType> */
    public static function getTypes(): array {
        self::checkInit();
        return self::$members;
    }

    public function __construct(private string $name) {}

    public function getName(): string {
        return $this->name;
    }
}