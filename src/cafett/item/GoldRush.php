<?php


namespace cafett\item;


use cafett\model\job\Miner;
use pocketmine\item\ItemIds;

class GoldRush extends SkillItem
{
    const NAME = "GoldRush";
    const ID = ItemIds::GOLDEN_NUGGET;
    const JOB_NAME = Miner::NAME;
}