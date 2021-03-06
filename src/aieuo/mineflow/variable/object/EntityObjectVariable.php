<?php

namespace aieuo\mineflow\variable\object;

use aieuo\mineflow\variable\DummyVariable;
use aieuo\mineflow\variable\NumberVariable;
use aieuo\mineflow\variable\StringVariable;
use aieuo\mineflow\variable\Variable;
use pocketmine\entity\Entity;

class EntityObjectVariable extends PositionObjectVariable {

    public function getValueFromIndex(string $index): ?Variable {
        $variable = parent::getValueFromIndex($index);
        if ($variable !== null) return $variable;

        $entity = $this->getEntity();
        switch ($index) {
            case "id":
                $variable = new NumberVariable($entity->getId(), "id");
                break;
            case "nameTag":
                $variable = new StringVariable($entity->getNameTag(), "nameTag");
                break;
            case "health":
                $variable = new NumberVariable($entity->getHealth(), "health");
                break;
            case "maxHealth":
                $variable = new NumberVariable($entity->getMaxHealth(), "maxHealth");
                break;
            case "yaw":
                $variable = new NumberVariable($entity->getYaw(), "yaw");
                break;
            case "pitch":
                $variable = new NumberVariable($entity->getPitch(), "pitch");
                break;
            case "direction":
                $variable = new NumberVariable($entity->getDirection(), "direction");
                break;
            default:
                return null;
        }
        return $variable;
    }

    public function getEntity(): Entity {
        /** @var Entity $value */
        $value = $this->getValue();
        return $value;
    }

    public static function getValuesDummy(string $name): array {
        return array_merge(parent::getValuesDummy($name), [
            new DummyVariable($name.".id", DummyVariable::NUMBER),
            new DummyVariable($name.".nameTag", DummyVariable::STRING),
            new DummyVariable($name.".health", DummyVariable::NUMBER),
            new DummyVariable($name.".maxHealth", DummyVariable::NUMBER),
            new DummyVariable($name.".yaw", DummyVariable::NUMBER),
            new DummyVariable($name.".pitch", DummyVariable::NUMBER),
            new DummyVariable($name.".direction", DummyVariable::NUMBER),
        ]);
    }
}