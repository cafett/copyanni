<?php


namespace cafett\item;


use cafett\model\job\Assassin;
use pocketmine\item\ItemIds;

class Leap extends SkillItem
{
    const NAME = "Leap";
    const ID = ItemIds::FEATHER;
    const JOB_NAME = Assassin::NAME;
}