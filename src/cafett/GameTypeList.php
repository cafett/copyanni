<?php


namespace cafett;


use game_chef\models\GameType;

class GameTypeList
{
    static function core(): GameType {
        return new GameType("CorePVP");
    }
}