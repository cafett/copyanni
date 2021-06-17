<?php


namespace copyanni\model\job;


use copyanni\item\GoldRush;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\Player;

class Miner extends Job
{
    const NAME = "Miner";
    const DESCRIPTION = "";

    public function __construct() {
        parent::__construct(
            [
                new GoldRush(),
                Item::get(ItemIds::COAL, 0, 8),
                Item::get(ItemIds::WOODEN_SWORD),
                Item::get(ItemIds::STONE_PICKAXE),
                Item::get(ItemIds::WOODEN_AXE),
                Item::get(ItemIds::FURNACE),
            ],
            [],
            60
        );
    }

    public function activateSkill(Player $player, ?Block $block = null): bool {
        $result = parent::activateSkill($player, $block);
        if ($result) {
            //todo skill
        }

        return $result;
    }
}