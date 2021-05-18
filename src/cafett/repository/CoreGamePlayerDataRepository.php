<?php


namespace cafett\repository;


use cafett\DataPath;
use cafett\model\CoreGamePlayerData;

class CoreGamePlayerDataRepository
{
    static function isExist(string $name):bool {
        return file_exists(DataPath::$coreGamePlayerData . $name . ".json");
    }

    static function load(string $name): CoreGamePlayerData {
        if (!file_exists(DataPath::$coreGamePlayerData . $name . ".json")) {
            throw new \LogicException("存在しないプレイヤーデータを({$name})取得することはできません");
        }

        $json = json_decode(file_get_contents(DataPath::$coreGamePlayerData . $name . ".json"), true);
        return CoreGamePlayerData::fromJSON($json);
    }

    static function save(CoreGamePlayerData $data): void {
        $json = $data->toJson();
        file_put_contents(DataPath::$coreGamePlayerData . $data->getName() . ".json", json_encode($json));
    }
}