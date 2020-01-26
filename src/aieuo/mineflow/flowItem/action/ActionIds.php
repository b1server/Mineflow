<?php

namespace aieuo\mineflow\flowItem\action;

interface ActionIds {

    const DO_NOTHING = "doNothing";
    const EVENT_CANCEL = "eventCancel";
    const SEND_FORM = "sendForm";

    const SEND_MESSAGE = "sendMessage";
    const SEND_TIP = "sendTip";
    const SEND_POPUP = "sendPopup";
    const BROADCAST_MESSAGE = "broadcastMessage";
    const SEND_MESSAGE_TO_OP= "sendMessageToOp";
    const SEND_TITLE = "sendTitle";

    const GET_ENTITY = "getEntity";
    const TELEPORT = "teleport";
    const MOTION = "motion";
    const SET_YAW = "setYaw";
    const ADD_DAMAGE = "addDamage";
    const SET_IMMOBILE = "setImmobile";
    const UNSET_IMMOBILE = "unsetImmobile";

    const SET_SLEEPING = "setSleeping";
    const SET_SITTING = "setSitting";

    const ADD_MONEY = "addMoney";
    const SET_MONEY = "setMoney";
    const TAKE_MONEY = "takeMoney";
    const GET_MONEY = "getMoney";

    const EXECUTE_RECIPE = "executeRecipe";
    const EXECUTE_RECIPE_WITH_ENTITY = "executeRecipeWithEntity";
    const CALL_RECIPE = "callRecipe";

    const FOUR_ARITHMETIC_OPERATIONS = "fourArithmeticOperations";
    const CALCULATE = "calculate";
    const GET_PI = "getPi";
    const GET_E = "getE";

    const ADD_VARIABLE = "addVariable";
    const DELETE_VARIABLE = "deleteVariable";
    const ADD_LIST_VARIABLE = "addListVariable";
    const ADD_MAP_VARIABLE = "addMapVariable";

    const ACTION_IF = "if";
    const ACTION_ELSEIF = "elseif";
    const ACTION_ELSE = "else";
    const ACTION_REPEAT = "repeat";
    const ACTION_WHILE = "while";
}