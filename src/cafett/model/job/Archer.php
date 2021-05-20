<?php


namespace cafett\model\job;


use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\Player;

class Archer extends Job
{
    const NAME = "Archer";
    const DESCRIPTION = "";

    public function __construct() {
        $bow = Item::get(ItemIds::BOW);
        $bow->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::PUNCH), 1));

        parent::__construct(
            [
                Item::get(ItemIds::ARROW, 0, 16),
                $bow,
                Item::get(ItemIds::WOODEN_SWORD),
                Item::get(ItemIds::WOODEN_PICKAXE),
                Item::get(ItemIds::WOODEN_AXE),
                Item::get(ItemIds::WOODEN_SHOVEL),
                Item::get(ItemIds::POTION, 21, 1),
            ],
            [],
            45
        );
    }

    public function activateSkill(Player $player): bool {
        $result = parent::activateSkill($player);
        if ($result) $player->getInventory()->addItem(Item::get(ItemIds::ARROW, 0, 32));
        return $result;
    }
}