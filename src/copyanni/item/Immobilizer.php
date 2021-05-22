<?php


namespace copyanni\item;


use pocketmine\item\ItemIds;

class Immobilizer extends SkillItem
{
    const NAME = "Immobilizer";
    const ID = ItemIds::SLIME_BALL;
    const JOB_NAME = \copyanni\model\job\Immobilizer::NAME;
}