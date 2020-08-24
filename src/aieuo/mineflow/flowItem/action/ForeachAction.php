<?php

namespace aieuo\mineflow\flowItem\action;

use aieuo\mineflow\formAPI\CustomForm;
use aieuo\mineflow\formAPI\element\Input;
use aieuo\mineflow\Main;
use aieuo\mineflow\ui\FlowItemForm;
use aieuo\mineflow\utils\Language;
use aieuo\mineflow\variable\ListVariable;
use aieuo\mineflow\variable\StringVariable;
use aieuo\mineflow\variable\Variable;
use pocketmine\Player;
use aieuo\mineflow\utils\Session;
use aieuo\mineflow\utils\Category;
use aieuo\mineflow\ui\ActionForm;
use aieuo\mineflow\ui\ActionContainerForm;
use aieuo\mineflow\recipe\Recipe;
use aieuo\mineflow\formAPI\ListForm;
use aieuo\mineflow\formAPI\element\Button;
use aieuo\mineflow\variable\NumberVariable;

class ForeachAction extends Action implements ActionContainer {
    use ActionContainerTrait {
        resume as traitResume;
    }

    protected $id = self::ACTION_FOREACH;

    protected $name = "action.foreach.name";
    protected $detail = "action.foreach.description";

    protected $category = Category::SCRIPT;

    protected $targetRequired = Recipe::TARGET_REQUIRED_NONE;

    protected $permission = self::PERMISSION_LEVEL_1;

    /** @var string */
    private $listVariableName = "list";
    /** @var string */
    private $keyVariableName = "key";
    /** @var string */
    private $valueVariableName = "value";

    /* @var bool */
    private $lastResult;

    /** @var array */
    private $counter;

    public function __construct(array $actions = [], ?string $customName = null) {
        $this->setActions($actions);
        $this->setCustomName($customName);
    }

    public function setValueVariableName(string $count): void {
        $this->valueVariableName = $count;
    }

    public function getValueVariableName(): string {
        return $this->valueVariableName;
    }

    public function setKeyVariableName(string $keyVariableName): self {
        $this->keyVariableName = $keyVariableName;
        return $this;
    }

    public function getKeyVariableName(): string {
        return $this->keyVariableName;
    }

    public function setListVariableName(string $listVariableName): self {
        $this->listVariableName = $listVariableName;
        return $this;
    }

    public function getListVariableName(): string {
        return $this->listVariableName;
    }

    public function getDetail(): string {
        $repeat = $this->getListVariableName()." as ".$this->getKeyVariableName()." => ".$this->getValueVariableName();

        $details = ["", "== foreach(".$repeat.") =="];
        foreach ($this->actions as $action) {
            $details[] = $action->getDetail();
        }
        $details[] = "================================";
        return implode("\n", $details);
    }

    public function getContainerName(): string {
        return empty($this->getCustomName()) ? $this->getName() : $this->getCustomName();
    }

    public function execute(Recipe $origin, bool $first = true): bool {
        if ($first) {
            $listName = $origin->replaceVariables($this->listVariableName);
            $list = $origin->getVariable($listName) ?? Main::getVariableHelper()->getNested($listName);
            $key = $origin->replaceVariables($this->keyVariableName);
            $value = $origin->replaceVariables($this->valueVariableName);

            if (!($list instanceof ListVariable)) {
                throw new \UnexpectedValueException(Language::get("flowItem.error", [$this->getName(), ["action.foreach.error.notVariable", [$listName]]]));
            }

            $this->initCounter($list, $key, $value);
        }

        $counter = $this->counter;

        for ($i = $counter["current"]; $i < $counter["size"]; $i ++) {
            $this->counter["current"] ++;

            $key = $counter["list"][$i][0];
            $keyVariable = is_numeric($key) ? new NumberVariable($key, $counter["keyName"]) : new StringVariable($key, $counter["keyName"]);
            $origin->addVariable($keyVariable);

            /** @var Variable $valueVariable */
            $valueVariable = clone $counter["list"][$i][1];
            $valueVariable->setName($counter["valueName"]);
            $origin->addVariable($valueVariable);

            if (!$this->executeActions($origin, $this->getParent())) return false;
            if ($this->wait or $this->isWaiting()) return true;
        }
        $this->getParent()->resume();
        return true;
    }

    public function resume() {
        $last = $this->next;

        $this->wait = false;
        $this->next = null;

        if (!$this->isWaiting()) return;

        $this->waiting = false;

        $this->executeActions(...$last);
        $this->execute($last[0], false);
    }

    public function initCounter(ListVariable $listVariable, string $key, string $value) {
        $list = [];
        foreach ($listVariable->getValue() as $k => $v) {
            $list[] = [$k, $v];
        }
        $this->counter = [
            "list" => $list,
            "keyName" => $key,
            "valueName" => $value,
            "current" => 0,
            "size" => count($list),
        ];
    }

    public function hasCustomMenu(): bool {
        return true;
    }

    public function sendCustomMenu(Player $player, array $messages = []): void {
        $detail = trim($this->getDetail());
        (new ListForm($this->getName()))->setContent(empty($detail) ? "@recipe.noActions" : $detail)
            ->addButtons([
                new Button("@form.back"),
                new Button("@action.edit"),
                new Button("@action.for.setting"),
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
                        (new ActionContainerForm)->sendActionList($player, $parent);
                        break;
                    case 1:
                        (new ActionContainerForm)->sendActionList($player, $this);
                        break;
                    case 2:
                        $this->sendSettingCounter($player);
                        break;
                    case 3:
                        (new FlowItemForm)->sendChangeName($player, $this, $parent);
                        break;
                    case 4:
                        (new ActionContainerForm)->sendMoveAction($player, $parent, array_search($this, $parent->getActions(), true));
                        break;
                    case 5:
                        (new ActionForm)->sendConfirmDelete($player, $this, $parent);
                        break;
                }
            })->onClose(function (Player $player) {
                Session::getSession($player)->removeAll();
            })->addMessages($messages)->show($player);
    }

    public function sendSettingCounter(Player $player, array $default = [], array $errors = []) {
        (new CustomForm("@action.for.setting"))
            ->setContents([
                new Input("@action.foreach.listVariableName",Language::get("form.example", ["list"]), $default[0] ?? $this->getListVariableName()),
                new Input("@action.foreach.keyVariableName", Language::get("form.example", ["key"]), $default[1] ?? $this->getKeyVariableName()),
                new Input("@action.foreach.valueVariableName", Language::get("form.example", ["value"]), $default[2] ?? $this->getValueVariableName()),
            ])->onReceive(function (Player $player, array $data) {
                for ($i = 0; $i < 3; $i++) {
                    if ($data[$i] === "") {
                        $this->sendSettingCounter($player, $data, [["@form.insufficient", $i]]);
                        return;
                    }
                }

                $this->setListVariableName($data[0]);
                $this->setKeyVariableName($data[1]);
                $this->setValueVariableName($data[2]);
                $this->sendCustomMenu($player, ["@form.changed"]);
            })->addErrors($errors)->show($player);
    }

    public function loadSaveData(array $contents): Action {
        if (!isset($contents[3])) throw new \OutOfBoundsException();

        foreach ($contents[0] as $content) {
            $action = Action::loadSaveDataStatic($content);
            $this->addAction($action);
        }

        $this->setListVariableName($contents[1]);
        $this->setKeyVariableName($contents[2]);
        $this->setValueVariableName($contents[3]);
        return $this;
    }

    public function serializeContents(): array {
        return [$this->actions, $this->listVariableName, $this->keyVariableName, $this->valueVariableName,];
    }

    public function isDataValid(): bool {
        return true;
    }

    public function allowDirectCall(): bool {
        return false;
    }
}