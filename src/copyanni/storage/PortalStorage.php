<?php


namespace copyanni\storage;


use copyanni\model\Portal;
use game_chef\models\GameId;
use game_chef\models\TeamId;
use pocketmine\math\Vector3;

//単純操作だけする
class PortalStorage
{
    //Portal[]
    static private array $anniGamePortals = [];

    static public function add(Portal $portal): void {
        self::$anniGamePortals[] = $portal;
    }

    static public function findPair(GameId $gameId, TeamId $teamId, Vector3 $pos): ?Vector3 {
        /** @var Portal $portal */
        foreach (self::$anniGamePortals as $portal) {
            if (!$portal->getGameId()->equals($gameId)) continue;
            if (!$portal->getTeamId()->equals($teamId)) continue;

            if ($portal->getPos1()->equals($pos)) {
                return $portal->getPos2();
            } else if ($portal->getPos2()->equals($pos)) {
                return $portal->getPos1();
            }
        }

        return null;
    }

    static public function delete(Portal $target): void {
        /** @var Portal $portal */
        foreach (self::$anniGamePortals as $key => $portal) {
            if ($portal->equals($target)) {
                unset(self::$anniGamePortals[$key]);

                self::$anniGamePortals = array_values(self::$anniGamePortals);
                return;
            }
        }
    }
}