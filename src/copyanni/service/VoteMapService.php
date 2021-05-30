<?php


namespace copyanni\service;


use copyanni\model\VoteId;
use game_chef\api\GameChef;
use pocketmine\level\Level;
use pocketmine\Server;

class VoteMapService
{
    const VoteWoldKey = "VoteMap";
    private static string $voteFolderName;

    static function init(string $folderName): void {
        self::$voteFolderName = $folderName;
    }

    static function generateVoteLevel(VoteId $voteId): void {
        GameChef::copyWorld(self::$voteFolderName, strval($voteId) . self::VoteWoldKey);
    }

    static function getVoteLevel(VoteId $voteId): Level {
        return Server::getInstance()->getLevelByName(strval($voteId) . self::VoteWoldKey);
    }

    static function isVoteLevel(string $levelName): bool {
        return strpos($levelName, self::VoteWoldKey) !== false;
    }
}