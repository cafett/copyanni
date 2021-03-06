<?php

namespace copyanni\storage;

use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\Player;

class PlayerDeviceDataStorage
{
    //todo:抜けたら削除するように
    const OS = ["unknown", "Android", "iOS", "OSX", "FireOS", "GearVR", "HoloLens", "Windows10", "Windows", "Dedicated", "Orbis", "NX", "Switch"];
    const TAP = ["Android", "iOS"];
    private static array $playerDeviceData = [];

    public static function save(LoginPacket $packet): void
    {
        $data = $packet->clientData;
        self::$playerDeviceData[$packet->username] = $data;
    }

    public static function isTap(Player $player): bool
    {
        $index = self::$playerDeviceData[$player->getName()]["DeviceOS"];
        $osName = self::OS[$index];
        return in_array($osName, self::TAP);
    }
}
