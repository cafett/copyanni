<?php


namespace copyanni;


use game_chef\models\GameType;
use game_chef\pmmp\bossbar\BossbarType;

class TypeList
{
    static function Anni(): GameType {
        return new GameType("anni");
    }

    static function VoteBossbar(): BossbarType {
        return new BossbarType("anni");
    }
}