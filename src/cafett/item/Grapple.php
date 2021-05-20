<?php


namespace cafett\item;


use cafett\model\job\Scout;
use pocketmine\block\Block;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Grapple extends SkillItem
{
    const NAME = "Grapple";
    const ID = ItemIds::FISHING_ROD;
    const JOB_NAME = Scout::NAME;

    public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector): bool {
        return true;
    }

    public function onClickAir(Player $player, Vector3 $directionVector): bool {
        return $this->use($player);
    }

    public function onClickPlayer(Player $player, Player $target): bool {
        return true;
    }
}