<?php


namespace cafett\model\job;


use cafett\item\Frenzy;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;

class Warrior extends Job
{
    const NAME = "Warrior";
    const DESCRIPTION = "";

    public function __construct() {
        parent::__construct(
            [
                new Frenzy(),
                Item::get(ItemIds::POTION, 21, 1),
                Item::get(ItemIds::WOODEN_SWORD),
                Item::get(ItemIds::WOODEN_PICKAXE),
                Item::get(ItemIds::WOODEN_AXE),
            ],
            [],
            60
        );
    }

    public function isOnFrenzy():bool {
        return $this->skillCoolTime >= $this->initialSkillCoolTime - 15;
    }
}