<?php

namespace aieuo\mineflow\trigger\event;

use aieuo\mineflow\variable\DefaultVariables;
use aieuo\mineflow\variable\DummyVariable;
use aieuo\mineflow\variable\StringVariable;
use aieuo\mineflow\variable\Variable;
use pocketmine\event\player\PlayerCommandPreprocessEvent;

class PlayerCommandPreprocessEventTrigger extends EventTrigger {
    public function __construct(string $subKey = "") {
        parent::__construct(PlayerCommandPreprocessEvent::class, $subKey);
    }

    /**
     * @param PlayerCommandPreprocessEvent $event
     * @return array<string, Variable>
     * @noinspection PhpMissingParamTypeInspection
     */
    public function getVariables($event): array {
        $target = $event->getPlayer();
        $variables = array_merge(DefaultVariables::getCommandVariables(substr($event->getMessage(), 1)), DefaultVariables::getPlayerVariables($target));
        $variables["message"] = new StringVariable($event->getMessage(), "message");
        return $variables;
    }

    public function getVariablesDummy(): array {
        return [
            "target" => new DummyVariable("target", DummyVariable::PLAYER),
            "message" => new DummyVariable("message", DummyVariable::STRING),
            "cmd" => new DummyVariable("cmd", DummyVariable::STRING),
            "args" => new DummyVariable("args", DummyVariable::LIST),
        ];
    }
}