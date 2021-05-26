<?php


namespace copyanni\model\job;


use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\Player;

class Acrobat extends Job
{
    const NAME = "Acrobat";
    const DESCRIPTION = "";

    public function __construct() {
        parent::__construct(
            [
                Item::get(ItemIds::ARROW, 0, 6),
                Item::get(ItemIds::BOW),
                Item::get(ItemIds::WOODEN_SWORD),
                Item::get(ItemIds::WOODEN_PICKAXE),
                Item::get(ItemIds::WOODEN_AXE),
            ],
            [],
            10
        );
    }

    public function activateSkill(Player $player): bool {
        $result = parent::activateSkill($player);
        if (!$result) return false;

        $player->knockBack($player, 0, $player->getDirectionVector()->getX(), $player->getDirectionVector()->getZ(), 1.3);
        return true;
    }
}