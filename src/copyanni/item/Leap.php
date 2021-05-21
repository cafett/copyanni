<?php


namespace copyanni\item;


use copyanni\model\job\Assassin;
use pocketmine\item\ItemIds;

class Leap extends SkillItem
{
    const NAME = "Leap";
    const ID = ItemIds::FEATHER;
    const JOB_NAME = Assassin::NAME;
}