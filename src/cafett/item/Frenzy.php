<?php


namespace cafett\item;


use cafett\model\job\Warrior;
use pocketmine\item\ItemIds;

class Frenzy extends SkillItem
{
    const NAME = "Frenzy";
    const ID = ItemIds::BOOK;
    const JOB_NAME = Warrior::NAME;
}