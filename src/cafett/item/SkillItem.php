<?php


namespace cafett\item;


use cafett\model\job\Miner;
use cafett\storage\CoreGamePlayerDataStorage;
use game_chef\api\GameChef;
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
        return $this->use($player, $blockClicked);
    }

    public function onClickAir(Player $player, Vector3 $directionVector): bool {
        return $this->use($player);
    }

    //todo:listerから呼び出す
    public function onClickPlayer(Player $player, Player $target): bool {
        return $this->use($player, null, $target);
    }

    protected function use(Player $player, ?Block $block = null, ?Player $target = null): bool {
        $playerData = GameChef::findPlayerData($player->getName());

        //todo:coreゲーム以外で使えなくする
        if ($playerData->getBelongGameId() === null) {
            $player->sendMessage("this item can be used on Game");
            $player->getInventory()->remove($this);
            return false;
        }

        $playerCoreGameData = CoreGamePlayerDataStorage::get($player->getName());
        if ($playerCoreGameData->getCurrentJob()::NAME !== self::JOB_NAME) {
            $player->sendMessage("you cannot use this item because of your job. this item can be used by only " . self::JOB_NAME);
            $player->getInventory()->remove($this);
            return false;
        }

        $playerCoreGameData->getCurrentJob()->activateSkill($player);
        return true;
    }
}