<?php


namespace copyanni\storage;

//repositoryも作る dbにはCoreGamePlayerDataの所持jobしか保存しない
use copyanni\model\AnniPlayerData;
use copyanni\repository\AnniPlayerDataRepository;

class AnniPlayerDataStorage
{
    private static array $dataList = [];

    static function get(string $name): AnniPlayerData {
        return self::$dataList[$name];
    }

    static function loadFromRepository(string $name): void {
        if (!AnniPlayerDataRepository::isExist($name)) {
            AnniPlayerDataRepository::save(AnniPlayerData::asNew($name));
        }

        self::$dataList[$name] = AnniPlayerDataRepository::load($name);
    }

    static function remove(string $name): void {
        AnniPlayerDataRepository::save(self::$dataList[$name]);
        unset(self::$dataList[$name]);
    }

    static function removeAll(): void {
        foreach (self::$dataList as $data) {
            AnniPlayerDataRepository::save($data);
        }

        self::$dataList = [];
    }
}