<?php


namespace copyanni\item;


use copyanni\model\job\Miner;
use pocketmine\item\ItemIds;

class GuardiansWarp extends SkillItem
{
    const NAME = "GuardiansWarp";
    const ID = ItemIds::ENDER_PEARL;
    const JOB_NAME = Miner::NAME;
}