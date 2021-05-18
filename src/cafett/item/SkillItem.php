<?php


namespace cafett\item;


use cafett\model\job\Miner;
use cafett\storage\CoreGamePlayerDataStorage;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\Player;

class SkillItem extends Item
{
    const NAME = "";
    const ID = ItemIds::GOLDEN_NUGGET;
    const JOB_NAME = "";

    public function __construct() {
        parent::__construct(self::ID, 0, self::NAME);
        $this->setCustomName($this->name);
    }

    public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector): bool {
        return $this->use($player);
    }

    public function onClickAir(Player $player, Vector3 $directionVector): bool {
        return $this->use($player);
    }

    private function use(Player $player):bool{
        $playerData = CoreGamePlayerDataStorage::get($player->getName());
        if ($playerData->getCurrentJob()::NAME !== Miner::NAME) {
            $player->sendMessage("you cannot use this item because of your job. this item can be used by only " . self::JOB_NAME);
            $player->getInventory()->remove($this);
            return false;
        }

        $playerData->getCurrentJob()->activateSkill($player);
        return true;
    }
}