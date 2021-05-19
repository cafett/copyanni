<?php


namespace cafett\model\job;


use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;

class Handyman extends Job
{
    const NAME = "Handyman";
    const DESCRIPTION = "";

    public function __construct() {
        $pickaxe = Item::get(ItemIds::WOODEN_PICKAXE);
        $pickaxe->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY)));
        parent::__construct(
            [
                Item::get(ItemIds::WOODEN_SWORD),
                $pickaxe,
                Item::get(ItemIds::WOODEN_AXE),
            ],
            [],
            0
        );
    }
}