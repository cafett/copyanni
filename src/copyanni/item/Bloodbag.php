<?php


namespace copyanni\item;


use copyanni\model\job\Healer;
use copyanni\storage\CoreGamePlayerDataStorage;
use game_chef\api\GameChef;
use pocketmine\block\Block;
use pocketmine\item\ItemIds;
use pocketmine\Player;

class Bloodbag extends SkillItem
{
    const NAME = "Bloodbag";
    const ID = ItemIds::COOKED_PORKCHOP;
    const JOB_NAME = Healer::NAME;


    protected function use(Player $player, ?Block $block = null, ?Player $target = null): bool {
        $playerData = GameChef::findPlayerData($player->getName());

        //todo:coreゲーム以外で使えなくする
        if ($playerData->getBelongGameId() === null) {
            $player->sendMessage("this item can be used on Game");
            $player->getInventory()->remove($this);
            return false;
        }

        $playerCoreGameData = CoreGamePlayerDataStorage::get($player->getName());
        $job = $playerCoreGameData->getCurrentJob();
        if (!($job instanceof Healer)) {
            $player->sendMessage("you cannot use this item because of your job. this item can be used by only " . self::JOB_NAME);
            $player->getInventory()->remove($this);
            return false;
        }

        if ($target !== null) {
            $job->activateByLeftClick($player, $target);
        } else {
            $job->activateByRightClick($player);
        }
    }
}