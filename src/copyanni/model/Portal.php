<?php


namespace copyanni\model;


use game_chef\models\GameId;
use game_chef\models\TeamId;
use pocketmine\math\Vector3;

class Portal
{
    private GameId $gameId;
    private TeamId $teamId;

    private Vector3 $pos1;
    private Vector3 $pos2;

    public function __construct(GameId $gameId, TeamId $teamId, Vector3 $pos1, Vector3 $pos2) {
        $this->gameId = $gameId;
        $this->teamId = $teamId;
        $this->pos1 = $pos1;
        $this->pos2 = $pos2;
    }

    /**
     * @return Vector3
     */
    public function getPos1(): Vector3 {
        return $this->pos1;
    }

    /**
     * @return Vector3
     */
    public function getPos2(): Vector3 {
        return $this->pos2;
    }

    public function equals(?Portal $portal): bool {
        if ($portal === null) return false;
        $vec = $this->pos1->equals($portal->pos1) and $this->pos2->equals($portal->pos2);
        return $vec and $this->gameId->equals($portal->gameId) and $this->teamId->equals($portal->teamId);
    }

    /**
     * @return GameId
     */
    public function getGameId(): GameId {
        return $this->gameId;
    }

    /**
     * @return TeamId
     */
    public function getTeamId(): TeamId {
        return $this->teamId;
    }
}