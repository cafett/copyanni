<?php


namespace copyanni\item\hotbar_menu;


use copyanni\form\SelectTeamForm;
use copyanni\form\VoteMapForm;
use copyanni\model\Vote;
use copyanni\model\VoteStatus;
use game_chef\pmmp\hotbar_menu\HotbarMenu;
use game_chef\pmmp\hotbar_menu\HotbarMenuItem;
use pocketmine\item\ItemIds;
use pocketmine\Player;

class VoteHotbarMenu extends HotbarMenu
{
    public function __construct(Player $player, Vote $vote) {
        $items = [];//todo:職業変更のアイテム
        if ($vote->getStatus()->equals(VoteStatus::MapElect())) {
            $items[] = new HotbarMenuItem(
                ItemIds::EMPTY_MAP,
                0,
                "マップ投票",
                function (Player $player) use ($vote) {
                    $player->sendForm(new VoteMapForm($vote->getId(), $vote->getMapOptions()));
                }
            );
        } else if ($vote->getStatus()->equals(VoteStatus::TeamSelect())) {
            $items[] = new HotbarMenuItem(
                ItemIds::EMERALD,
                0,
                "チーム選択",
                function (Player $player) use ($vote) {
                    $player->sendForm(new SelectTeamForm($vote->getGameId()));
                }
            );
        }


        parent::__construct($player, $items);
    }

}