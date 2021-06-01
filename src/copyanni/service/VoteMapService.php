<?php


namespace copyanni\service;


use copyanni\model\VoteId;
use game_chef\api\GameChef;
use pocketmine\level\Level;
use pocketmine\Server;

class VoteMapService
{
    const VoteWoldKey = "VoteMap";//configのkey+map名
    private static string $voteMapName;

    static function init(string $folderName): void {
        self::$voteMapName = $folderName;
    }

    static function generateVoteLevel(VoteId $voteId): void {
        GameChef::copyWorld(self::$voteMapName, self::$voteMapName . strval($voteId) . self::VoteWoldKey);
    }

    static function getVoteLevel(VoteId $voteId): Level {
        $name = self::$voteMapName . strval($voteId) . self::VoteWoldKey;
        return GameChef::getWorld($name, true);
    }

    static function isVoteLevel(string $levelName): bool {
        return strpos($levelName, self::VoteWoldKey) !== false;
    }
}