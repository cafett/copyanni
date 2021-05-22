<?php


namespace copyanni\repository;


use copyanni\DataPath;
use copyanni\model\AnniPlayerData;

class AnniPlayerDataRepository
{
    static function isExist(string $name):bool {
        return file_exists(DataPath::$anniPlayerData . $name . ".json");
    }

    static function load(string $name): AnniPlayerData {
        if (!file_exists(DataPath::$anniPlayerData . $name . ".json")) {
            throw new \LogicException("存在しないプレイヤーデータを({$name})取得することはできません");
        }

        $json = json_decode(file_get_contents(DataPath::$anniPlayerData . $name . ".json"), true);
        return AnniPlayerData::fromJSON($json);
    }

    static function save(AnniPlayerData $data): void {
        $json = $data->toJson();
        file_put_contents(DataPath::$anniPlayerData . $data->getName() . ".json", json_encode($json));
    }
}