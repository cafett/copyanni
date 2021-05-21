<?php


namespace cafett\item;


use cafett\model\job\Builder;
use pocketmine\item\ItemIds;

class ResourceDrop extends SkillItem
{
    const NAME = "ResourceDrop";
    const ID = ItemIds::BOOK;
    const JOB_NAME = Builder::NAME;
}