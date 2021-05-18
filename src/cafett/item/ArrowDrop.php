<?php


namespace cafett\item;


use cafett\model\job\Archer;
use pocketmine\item\ItemIds;

class ArrowDrop extends SkillItem
{
    const NAME = "ArrowDrop";
    const ID = ItemIds::BOOK;
    const JOB_NAME = Archer::NAME;
}