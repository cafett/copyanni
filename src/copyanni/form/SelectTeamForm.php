<?php


namespace copyanni\form;


use copyanni\scoreboard\VoteScoreboard;
use copyanni\service\VoteMapService;
use copyanni\storage\VoteStorage;
use form_builder\models\simple_form_elements\SimpleFormButton;
use form_builder\models\SimpleForm;
use game_chef\api\GameChef;
use game_chef\models\GameId;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class SelectTeamForm extends SimpleForm
{
    public function __construct(GameId $gameId) {
        $game = GameChef::findGameById($gameId);
        $buttons = [];
        foreach ($game->getTeams() as $team) {
            $count = count(GameChef::getTeamPlayerDataList($team->getId()));
            $teamId = $team->getId();
            $buttons[] = new SimpleFormButton(
                $team->getTeamColorFormat() . $team->getName() . TextFormat::RESET . " : $count",
                null,
                function (Player $player) use ($gameId, $teamId) {
                    $result = GameChef::joinTeamGame($player, $gameId, $teamId);
                    if ($result) {
                        $vote = VoteStorage::getByGameId($gameId);
                        VoteScoreboard::update(VoteMapService::getVoteLevel($vote->getId())->getPlayers(), $vote);
                    }
                }
            );
        }
        parent::__construct("チーム選択", "", $buttons);
    }

    function onClickCloseButton(Player $player): void {}
}