<?php

namespace pocketcloud\cloud\bridge\form\util;

use pocketcloud\cloud\bridge\api\object\group\ServerGroup;
use pocketcloud\cloud\bridge\api\object\player\CloudPlayer;
use pocketcloud\cloud\bridge\api\object\server\CloudServer;
use pocketcloud\cloud\bridge\api\object\template\Template;
use pocketcloud\cloud\bridge\api\provider\CloudPlayerProvider;
use pocketcloud\cloud\bridge\api\provider\CloudServerProvider;
use pocketcloud\cloud\bridge\api\provider\ServerGroupProvider;
use pocketcloud\cloud\bridge\api\provider\TemplateProvider;
use pocketcloud\cloud\bridge\util\trait\RegistryTrait;
use pocketmine\player\Player;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use pocketmine\utils\TextFormat;
use r3pt1s\forms\builder\MenuFormBuilder;
use r3pt1s\forms\element\menu\MenuOption;

/**
 * @method static FormFilterMechanism TEMPLATE(?string $template)
 * @method static FormFilterMechanism SERVER_GROUP(?string $serverGroup)
 * @method static FormFilterMechanism SERVER(?string $server)
 * @method static FormFilterMechanism PLAYER_COUNT(?bool $inverted) <- regular: from high to low, inverted means from low to high
 */
final class FormFilterMechanism {
    use RegistryTrait;

    public function __construct(
        private readonly string $name,
        private ?array $data
    ) {}

    public static function awaitMechanismOption(Player $player): Promise {
        self::check();
        $resolver = new PromiseResolver();
        $originElements = [new MenuOption("§cRemove filter")];

        $player->sendForm(MenuFormBuilder::create("Choose a filter")
            ->elements(array_merge($originElements, array_map(fn(string $key) => new MenuOption(implode(" ", array_map(fn(string $s) => ucfirst(strtolower($s)), explode("_", $key))), extraData: [$key]), array_keys(self::$members))))
            ->onSubmit(function (Player $player, int $index, MenuOption $option) use ($resolver): void {
                if ($index == 0) {
                    $resolver->resolve(null);
                    return;
                }

                $formMechanism = self::{strtoupper($option->get(0, "TEMPLATE"))}(null);
                if ($formMechanism instanceof FormFilterMechanism) {
                    $formMechanism->awaitDataOption($player)->onCompletion(
                        fn(?FormFilterMechanism $mechanism) => $resolver->resolve($mechanism),
                        fn() => $resolver->reject()
                    );
                } else $resolver->resolve(null);
            })
            ->onCancel(fn() => $resolver->reject())
            ->build()
        );

        return $resolver->getPromise();
    }

    public function awaitDataOption(Player $player): Promise {
        $originElements = [new MenuOption("§cRemove filter")];
        $resolver = new PromiseResolver();
        switch ($this->name) {
            case "template": {
                $player->sendForm(MenuFormBuilder::create("Choose a template")
                    ->elements(array_merge($originElements, array_map(fn(Template $template) => new MenuOption("§b" .
                        $template->getName()), TemplateProvider::provider()->getAll())))
                    ->onSubmit(function (Player $_, int $index, MenuOption $option) use ($resolver): void {
                        if ($index == 0) {
                            $this->data = null;
                            $resolver->resolve(null);
                        } else {
                            $this->data = [TextFormat::clean($option->getText())];
                            $resolver->resolve($this);
                        }
                    })
                    ->onCancel(fn() => $resolver->reject())
                    ->build());
                break;
            }
            case "server_group": {
                $player->sendForm(MenuFormBuilder::create("Choose a server group")
                    ->elements(array_merge($originElements, array_map(fn(ServerGroup $serverGroup) => new MenuOption("§b" .
                        $serverGroup->getName()), ServerGroupProvider::provider()->getAll())))
                    ->onSubmit(function (Player $_, int $index, MenuOption $option) use ($resolver): void {
                        if ($index == 0) {
                            $this->data = null;
                            $resolver->resolve(null);
                        } else {
                            $this->data = [TextFormat::clean($option->getText())];
                            $resolver->resolve($this);
                        }
                    })
                    ->onCancel(fn() => $resolver->reject())
                    ->build());
                break;
            }
            case "server": {
                $player->sendForm(MenuFormBuilder::create("Choose a server")
                    ->elements(array_merge($originElements, array_map(fn(CloudServer $server) => new MenuOption("§b" . $server->getName()), CloudServerProvider::provider()->getAll())))
                    ->onSubmit(function (Player $_, int $index, MenuOption $option) use ($resolver): void {
                        if ($index == 0) {
                            $this->data = null;
                            $resolver->resolve(null);
                        } else {
                            $this->data = [TextFormat::clean($option->getText())];
                            $resolver->resolve($this);
                        }
                    })
                    ->onCancel(fn() => $resolver->reject())
                    ->build());
                break;
            }
            case "player_count": {
                $player->sendForm(MenuFormBuilder::create("Choose an option")
                    ->elements(array_merge($originElements, [new MenuOption("High -> Low"), new MenuOption("Low -> High")]))
                    ->onSubmit(function (Player $_, int $index, MenuOption $option) use ($resolver, $originElements): void {
                        if ($index == 0) {
                            $this->data = null;
                            $resolver->resolve(null);
                        } else {
                            $this->data = [$index == count($originElements)];
                            $resolver->resolve($this);
                        }
                    })
                    ->onCancel(fn() => $resolver->reject())
                    ->build());
                break;
            }
            default: {
                $resolver->reject();
                break;
            }
        }

        return $resolver->getPromise();
    }

    public function getName(): string {
        return $this->name;
    }

    protected static function init(): void {
        self::register($name = "template", function (?string $template) use ($name): FormFilterMechanism {
            return new FormFilterMechanism($name, is_null($template) ? null : [$template]);
        });

        self::register($name = "server_group", function (?string $serverGroup) use ($name): FormFilterMechanism {
            return new FormFilterMechanism($name, is_null($serverGroup) ? null : [$serverGroup]);
        });

        self::register($name = "server", function (?string $server) use ($name): FormFilterMechanism {
            return new FormFilterMechanism($name, is_null($server) ? null : [$server]);
        });

        self::register($name = "player_count", function (?bool $inverted) use ($name): FormFilterMechanism {
            return new FormFilterMechanism($name, is_null($inverted) ? null : [$inverted]);
        });
    }

    public function filter(array $array): array {
        if ($this->data === null) return [];
        $filteredArray = [];
        switch ($this->name) {
            case "template": {
                $template = TemplateProvider::provider()->get($templateName = $this->data[0]);
                if ($template === null) break;
                foreach ($array as $item) {
                    if ($item instanceof CloudServer) {
                        if ($item->getTemplateName() == $templateName) {
                            $filteredArray[] = $item;
                        }
                    } else if ($item instanceof CloudPlayer) {
                        if (str_starts_with($item->getCurrentServerName(), $templateName) || str_starts_with($item->getCurrentProxyName(), $templateName)) {
                            $filteredArray[] = $item;
                        }
                    }
                }

                break;
            }
            case "server_group": {
                $serverGroup = ServerGroupProvider::provider()->get($serverGroupName = $this->data[0]);
                if ($serverGroup === null) break;
                foreach ($array as $item) {
                    if ($item instanceof CloudServer) {
                        if ($item->getTemplate()?->getParentServerGroup()?->getName() == $serverGroupName) {
                            $filteredArray[] = $item;
                        }
                    } else if ($item instanceof Template) {
                        if ($item->getParentServerGroup()?->getName() == $serverGroupName) {
                            $filteredArray[] = $item;
                        }
                    } else if ($item instanceof CloudPlayer) {
                        if (
                            $item->getCurrentServer()?->getTemplate()?->getParentServerGroup()?->getName() == $serverGroupName ||
                            $item->getCurrentProxy()?->getTemplate()?->getParentServerGroup()?->getName() == $serverGroupName
                        ) {
                            $filteredArray[] = $item;
                        }
                    }
                }

                break;
            }
            case "server": {
                $server = CloudServerProvider::provider()->get($serverName = $this->data[0]);
                if ($server === null) break;
                foreach ($array as $item) {
                    if ($item instanceof CloudServer) {
                        if ($item->getName() == $serverName) {
                            $filteredArray[] = $item;
                        }
                    } else if ($item instanceof CloudPlayer) {
                        if ($item->getCurrentServerName() == $serverName || $item->getCurrentProxyName() == $serverName) {
                            $filteredArray[] = $item;
                        }
                    }
                }

                break;
            }
            case "player_count": {
                $inverted = $this->data[0] ?? false;
                $preFilteredArray = [];
                foreach ($array as $item) {
                    if ($item instanceof CloudServer) {
                        if ($item->getPlayerCount() !== 0) $preFilteredArray["server:" . $item->getName()] = $item->getPlayerCount();
                    } else if ($item instanceof Template) {
                        if ($item->getPlayerCount() !== 0) $preFilteredArray["template:" . $item->getName()] = $item->getPlayerCount();
                    } else if ($item instanceof ServerGroup) {
                        if ($item->getPlayerCount() !== 0) $preFilteredArray["server_group:" . $item->getName()] = $item->getPlayerCount();
                    } else if ($item instanceof CloudPlayer) {
                        $preFilteredArray["player:" . $item->getName()] = strlen($item->getName());
                    }
                }

                if ($inverted) arsort($preFilteredArray);
                else asort($preFilteredArray);

                foreach ($preFilteredArray as $id => $_) {
                    $actualItem = null;
                    $parts = explode(":", $id);
                    $partId = array_shift($parts);
                    $rest = implode(":", $parts);
                    if ($partId === null) continue;
                    switch ($partId) {
                        case "server": {
                            $actualItem = CloudServerProvider::provider()->get($rest);
                            break;
                        }
                        case "template": {
                            $actualItem = TemplateProvider::provider()->get($rest);
                            break;
                        }
                        case "server_group": {
                            $actualItem = ServerGroupProvider::provider()->get($rest);
                            break;
                        }
                        case "player": {
                            $actualItem = CloudPlayerProvider::provider()->get($rest);
                            break;
                        }
                    }

                    if ($actualItem === null) continue;
                    $filteredArray[] = $actualItem;
                }
            }
            default: {}
        }

        return $filteredArray;
    }

    public function getData(): ?array {
        return $this->data;
    }
}