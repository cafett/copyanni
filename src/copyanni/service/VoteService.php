<?php


namespace copyanni\service;


use copyanni\item\hotbar_menu\VoteHotbarMenu;
use copyanni\model\VoteId;
use copyanni\model\VoteStatus;
use copyanni\scoreboard\VoteScoreboard;
use copyanni\storage\AnniPlayerDataStorage;
use copyanni\storage\VoteStorage;
use copyanni\TypeList;
use game_chef\pmmp\bossbar\Bossbar;
use pocketmine\Player;

class VoteService
{

    static function join(Player $player, VoteId $voteId): bool {
        $vote = VoteStorage::find($voteId);
        if ($vote === null) return false;

        //todo:前回参加していた試合のvoteなら、okにする
        if ($vote->getStatus()->equals(VoteStatus::DeclineNewPlayers())) return false;

        //scoreboard
        VoteScoreboard::send($player, $vote);

        //todo:参加人数を表示する
        //bossbar
        $bossbar = new Bossbar($player, TypeList::VoteBossbar(), $vote->getStatus()->toJPText(), 1.0);
        $bossbar->send();

        //teleport
        $level = VoteMapService::getVoteLevel($voteId);
        $player->teleport($level->getSpawnLocation());

        //menu
        $menu = new VoteHotbarMenu($player, $vote);
        $menu->send();

        //マップ選択中 and 参加人数が16人以上
        if ($vote->getStatus()->equals(VoteStatus::MapElect()) and $vote->getPlayersCount() >= 16) {
            $vote->startMapElectTimer();
        }

        $anniPlayerData = AnniPlayerDataStorage::get($player->getName());
        $anniPlayerData->setBelongVoteId($voteId);

        $vote->join($player);
        return true;
    }

    static function quit(Player $player): void {
        $anniPlayerData = AnniPlayerDataStorage::get($player->getName());
        $voteId = $anniPlayerData->getBelongVoteId();
        $vote = VoteStorage::find($voteId);
        if ($vote === null) return;

        $vote->quit($player);

        $remainPlayers = [];
        foreach (VoteMapService::getVoteLevel($voteId)->getPlayers() as $participant) {
            if ($participant->getName() !== $player->getName()) {
                $remainPlayers[] = $participant;
            }
        }
        VoteScoreboard::update($remainPlayers, $vote);

        $bossbar = Bossbar::findByType($player, TypeList::VoteBossbar());
        if ($bossbar !== null) $bossbar->remove();
        VoteScoreboard::delete($player);
    }
}