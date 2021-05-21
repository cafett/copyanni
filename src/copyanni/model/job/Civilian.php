<?php


namespace copyanni\model\job;


use pocketmine\item\Item;
use pocketmine\item\ItemIds;

class Civilian extends Job
{
    const NAME = "Civilian";
    const DESCRIPTION = "";

    public function __construct() {
        parent::__construct(
            [
                Item::get(ItemIds::WOODEN_SWORD),
                Item::get(ItemIds::STONE_PICKAXE),
                Item::get(ItemIds::STONE_AXE),
                Item::get(ItemIds::STONE_SHOVEL),
            ],
            [],
            10
        );
    }
}