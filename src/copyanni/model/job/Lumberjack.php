<?php


namespace copyanni\model\job;


use copyanni\item\BruteForce;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;

class Lumberjack extends Job
{
    const NAME = "Lumberjack";
    const DESCRIPTION = "";

    public function __construct() {
        $axe = Item::get(ItemIds::STONE_AXE);
        $axe->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY)));
        parent::__construct(
            [
                new BruteForce(),
                Item::get(ItemIds::WOODEN_SWORD),
                Item::get(ItemIds::WOODEN_PICKAXE),
                $axe,
            ],
            [],
            45
        );
    }

    public function isOnBruteForce():bool{
        return ($this->initialSkillCoolTime - $this->skillCoolTime) <= 10;
    }
}