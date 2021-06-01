<?php


namespace copyanni\scoreboard;


use copyanni\model\Vote;
use copyanni\model\VoteStatus;
use game_chef\api\GameChef;
use game_chef\pmmp\scoreboard\Score;
use game_chef\pmmp\scoreboard\Scoreboard;
use game_chef\pmmp\scoreboard\ScoreboardSlot;
use game_chef\pmmp\scoreboard\ScoreSortType;
use pocketmine\Player;

class VoteScoreboard extends Scoreboard
{
    static function init() {
        self::__setup(ScoreboardSlot::sideBar());
    }

    private static function create(Vote $vote): Scoreboard {
        $scores = [];

        if ($vote->getStatus()->equals(VoteStatus::MapElect())) {
            $statusText = "マップ選択 :";
            $electResult = $vote->getMapElectResult();
            foreach ($vote->getMapOptions() as $mapOption) {
                $statusText .= "\n >$mapOption:" . $electResult[$mapOption];
            }
            $scores[] = new Score($statusText);
        } else if ($vote->getGameId() !== null) {
            $statusText = "チーム選択 :";
            $game = GameChef::findGameById($vote->getGameId());
            foreach ($game->getTeams() as $team) {
                $players = count(GameChef::getTeamPlayerDataList($team->getId()));
                $statusText .= "\n >" . $team->getTeamColorFormat() . $team->getName() . ":$players";
            }
            $scores[] = new Score($statusText);
        }

        return parent::__create("===============", $scores, ScoreSortType::smallToLarge());
    }

    static function send(Player $player, Vote $vote) {
        $scoreboard = self::create($vote);
        parent::__send($player, $scoreboard);
    }

    static function update(array $players, Vote $vote) {
        $scoreboard = self::create($vote);
        foreach ($players as $player) {
            parent::__update($player, $scoreboard);
        }
    }
}