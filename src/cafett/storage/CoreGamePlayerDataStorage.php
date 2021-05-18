<?php


namespace cafett\storage;

//repositoryも作る dbにはCoreGamePlayerDataの所持jobしか保存しない
use cafett\model\CoreGamePlayerData;
use cafett\repository\CoreGamePlayerDataRepository;

class CoreGamePlayerDataStorage
{
    private static array $dataList = [];

    static function get(string $name): CoreGamePlayerData {
        return self::$dataList[$name];
    }

    static function loadFromRepository(string $name): void {
        if (!CoreGamePlayerDataRepository::isExist($name)) {
            CoreGamePlayerDataRepository::save(CoreGamePlayerData::asNew($name));
        }

        self::$dataList[$name] = CoreGamePlayerDataRepository::load($name);
    }

    static function save(string $name): void {
        CoreGamePlayerDataRepository::save(self::$dataList[$name]);
        unset(self::$dataList[$name]);
    }

    static function saveAll(): void {
        foreach (self::$dataList as $data) {
            CoreGamePlayerDataRepository::save($data);
        }

        self::$dataList = [];
    }
}