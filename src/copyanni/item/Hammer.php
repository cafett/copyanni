<?php


namespace copyanni\item;


use copyanni\model\job\Thor;
use pocketmine\block\Block;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Hammer extends SkillItem
{
    const NAME = "Hammer";
    const ID = ItemIds::GOLD_AXE;
    const JOB_NAME = Thor::NAME;

    public function __construct() {
        parent::__construct();
        $this->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::KNOCKBACK)));
        $this->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING)));
    }

    public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector): bool {
        return false;
    }

    public function onClickAir(Player $player, Vector3 $directionVector): bool {
        return false;
    }

    public function onClickPlayer(Player $player, Player $target): bool {
        return false;
    }

    public function rightClick(Player $player): bool {
        return $this->use($player);
    }
}