<?php


namespace copyanni\item;


use copyanni\model\job\Lumberjack;
use pocketmine\item\ItemIds;

class BruteForce extends SkillItem
{
    const NAME = "BruteForce";
    const ID = ItemIds::BOOK;
    const JOB_NAME = Lumberjack::NAME;
}