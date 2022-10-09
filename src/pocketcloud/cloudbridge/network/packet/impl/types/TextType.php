<?php

namespace pocketcloud\cloudbridge\network\packet\impl\types;

use pocketmine\utils\RegistryTrait;

/**
 * @method static TextType MESSAGE()
 * @method static TextType POPUP()
 * @method static TextType TIP()
 * @method static TextType TITLE()
 * @method static TextType ACTION_BAR()
 */
final class TextType {
    use RegistryTrait;

    protected static function setup(): void {
        self::_registryRegister("message", new TextType("MESSAGE"));
        self::_registryRegister("popup", new TextType("POPUP"));
        self::_registryRegister("tip", new TextType("TIP"));
        self::_registryRegister("title", new TextType("TITLE"));
        self::_registryRegister("action_bar", new TextType("ACTION_BAR"));
    }

    public static function getTypeByName(string $name): ?TextType {
        $textType = self::_registryFromString($name);
        if ($textType instanceof TextType) return $textType;
        return null;
    }

    public function __construct(private string $name) {}

    public function getName(): string {
        return $this->name;
    }
}