<?php


namespace copyanni;


class DataPath
{
    static string $coreGamePlayerData;

    static function init(string $dataPath) {
        self::$coreGamePlayerData = $dataPath . "team_game_maps" . DIRECTORY_SEPARATOR;
        if (!file_exists(self::$coreGamePlayerData)) mkdir(self::$coreGamePlayerData);
    }
}