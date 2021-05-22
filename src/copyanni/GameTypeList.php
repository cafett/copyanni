<?php


namespace copyanni;


use game_chef\models\GameType;

class GameTypeList
{
    static function anni(): GameType {
        return new GameType("anni");
    }
}