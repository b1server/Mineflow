<?php

namespace aieuo\mineflow\flowItem\action;

use aieuo\mineflow\flowItem\FlowItem;
use aieuo\mineflow\flowItem\FlowItemContainer;
use aieuo\mineflow\flowItem\FlowItemContainerTrait;
use aieuo\mineflow\formAPI\CustomForm;
use aieuo\mineflow\formAPI\element\Button;
use aieuo\mineflow\formAPI\element\mineflow\CancelToggle;
use aieuo\mineflow\formAPI\element\mineflow\ExampleNumberInput;
use aieuo\mineflow\formAPI\ListForm;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\ui\FlowItemContainerForm;
use aieuo\mineflow\ui\FlowItemForm;
use aieuo\mineflow\utils\Category;
use aieuo\mineflow\utils\Session;
use aieuo\mineflow\variable\NumberVariable;
use pocketmine\Player;

class WhileTaskAction extends FlowItem implements FlowItemContainer {
    use FlowItemContainerTrait;

    protected $id = self::ACTION_WHILE_TASK;

    protected $name = "action.whileTask.name";
    protected $detail = "action.whileTask.description";

    protected $category = Category::SCRIPT;

    protected $targetRequired = Recipe::TARGET_REQUIRED_NONE;

    protected $permission = self::PERMISSION_LEVEL_1;

    /** @var int */
    private $interval;
    /** @var int */
    private $limit = -1;

    /** @var int */
    private $loopCount = 0;

    public function __construct(array $conditions = [], array $actions = [], int $interval = 20, ?string $customName = null) {
        $this->setItems($conditions, FlowItemContainer::CONDITION);
        $this->setItems($actions, FlowItemContainer::ACTION);
        $this->interval = $interval;
        $this->setCustomName($customName);
    }

    public function setLimit(int $limit): void {
        $this->limit = $limit;
    }

    public function getLimit(): int {
        return $this->limit;
    }

    public function setInterval(int $interval): void {
        $this->interval = $interval;
    }

    public function getInterval(): int {
        return $this->interval;
    }

    public function getDetail(): string {
        $details = ["", "=========whileTask(".$this->getInterval().")========="];
        foreach ($this->getItems(FlowItemContainer::CONDITION) as $condition) {
            $details[] = $condition->getDetail();
        }
        $details[] = "~~~~~~~~~~~~~~~~~~~~~~~~~~~";
        foreach ($this->getItems(FlowItemContainer::ACTION) as $action) {
            $details[] = $action->getDetail();
        }
        $details[] = "================================";
        return implode("\n", $details);
    }

    public function getContainerName(): string {
        return empty($this->getCustomName()) ? $this->getName() : $this->getCustomName();
    }

    public function execute(Recipe $origin) {
        $wait = new Wait(strval($this->getInterval() / 20));
        while (true) {
            $origin->addVariable(new NumberVariable($this->loopCount, "i"));
            foreach ($this->getItems(FlowItemContainer::CONDITION) as $i => $condition) {
                if (!(yield from $condition->execute($origin))) {
                    $origin->resume();
                    return true;
                }
            }

            yield from $this->executeAll($origin, "action");
            yield from $wait->execute($origin);
        }
    }

    public function hasCustomMenu(): bool {
        return true;
    }

    public function sendCustomMenu(Player $player, array $messages = []): void {
        $detail = trim($this->getDetail());
        (new ListForm($this->getName()))
            ->setContent(empty($detail) ? "@recipe.noActions" : $detail)
            ->addButtons([
                new Button("@form.back"),
                new Button("@condition.edit"),
                new Button("@action.edit"),
                new Button("@action.whileTask.editInterval"),
                new Button("@form.home.rename.title"),
                new Button("@form.move"),
                new Button("@form.delete"),
            ])->onReceive(function (Player $player, int $data) {
                $session = Session::getSession($player);
                $parents = $session->get("parents");
                $parent = end($parents);
                switch ($data) {
                    case 0:
                        $session->pop("parents");
                        (new FlowItemContainerForm)->sendActionList($player, $parent, FlowItemContainer::ACTION);
                        break;
                    case 1:
                        (new FlowItemContainerForm)->sendActionList($player, $this, FlowItemContainer::CONDITION);
                        break;
                    case 2:
                        (new FlowItemContainerForm)->sendActionList($player, $this, FlowItemContainer::ACTION);
                        break;
                    case 3:
                        $this->sendSetWhileIntervalForm($player);
                        break;
                    case 4:
                        (new FlowItemForm)->sendChangeName($player, $this, $parent, FlowItemContainer::ACTION);
                        break;
                    case 5:
                        (new FlowItemContainerForm)->sendMoveAction($player, $parent, FlowItemContainer::ACTION, array_search($this, $parent->getActions(), true));
                        break;
                    case 6:
                        (new FlowItemForm)->sendConfirmDelete($player, $this, $parent, FlowItemContainer::ACTION);
                        break;
                }
            })->onClose(function (Player $player) {
                Session::getSession($player)->removeAll();
            })->addMessages($messages)->show($player);
    }

    public function sendSetWhileIntervalForm(Player $player, array $default = [], array $errors = []) {
        (new CustomForm("@action.repeat.editCount"))
            ->setContents([
                new ExampleNumberInput("@action.whileTask.interval", "20", $this->getInterval(), true, 1),
                new CancelToggle()
            ])->onReceive(function (Player $player, array $data) {
                if ($data[1]) {
                    $this->sendCustomMenu($player, ["@form.cancelled"]);
                    return;
                }

                $this->setInterval((int)$data[0]);
                $this->sendCustomMenu($player, ["@form.changed"]);
            })->addErrors($errors)->show($player);
    }

    public function loadSaveData(array $contents): FlowItem {
        foreach ($contents[0] as $content) {
            switch ($content["id"]) {
                case "removeItem":
                    $content["id"] = self::REMOVE_ITEM_CONDITION;
                    break;
                case "takeMoney":
                    $content["id"] = self::TAKE_MONEY_CONDITION;
                    break;
            }
            $condition = FlowItem::loadSaveDataStatic($content);
            $this->addItem($condition, FlowItemContainer::CONDITION);
        }

        foreach ($contents[1] as $content) {
            $action = FlowItem::loadSaveDataStatic($content);
            $this->addItem($action, FlowItemContainer::ACTION);
        }

        $this->setInterval($contents[2] ?? 20);
        $this->setLimit($contents[3] ?? -1);
        return $this;
    }

    public function serializeContents(): array {
        return  [
            $this->getItems(FlowItemContainer::CONDITION),
            $this->getItems(FlowItemContainer::ACTION),
            $this->interval,
            $this->limit,
        ];
    }

    public function isDataValid(): bool {
        return true;
    }

    public function allowDirectCall(): bool {
        return false;
    }

    public function __clone() {
        $conditions = [];
        foreach ($this->getItems(FlowItemContainer::CONDITION) as $k => $condition) {
            $conditions[$k] = clone $condition;
        }
        $this->setItems($conditions, FlowItemContainer::CONDITION);

        $actions = [];
        foreach ($this->getItems(FlowItemContainer::ACTION) as $k => $action) {
            $actions[$k] = clone $action;
        }
        $this->setItems($actions, FlowItemContainer::ACTION);
    }
}