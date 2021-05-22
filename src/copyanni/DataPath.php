<?php


namespace copyanni;


class DataPath
{
    static string $anniPlayerData;

    static function init(string $dataPath) {
        self::$anniPlayerData = $dataPath . "player_data" . DIRECTORY_SEPARATOR;
        if (!file_exists(self::$anniPlayerData)) mkdir(self::$anniPlayerData);
    }
}