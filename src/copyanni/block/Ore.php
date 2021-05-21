<?php


namespace copyanni\block;


use pocketmine\block\BlockIds;

class Ore
{
    const IDS = [
        //ore
        BlockIds::DIAMOND_ORE,
        BlockIds::GOLD_ORE,
        BlockIds::IRON_ORE,
        BlockIds::COAL_ORE,
        BlockIds::EMERALD_ORE,
        BlockIds::LAPIS_ORE,
        BlockIds::REDSTONE_ORE,
    ];

    const GENERATING_COOL_TIMES = [
        BlockIds::DIAMOND_ORE => 30,
        BlockIds::GOLD_ORE => 20,
        BlockIds::IRON_ORE => 20,
        BlockIds::COAL_ORE => 10,
        BlockIds::EMERALD_ORE => 40,
        BlockIds::LAPIS_ORE => 20,
        BlockIds::REDSTONE_ORE => 20,
    ];
}