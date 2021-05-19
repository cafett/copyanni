<?php


namespace cafett\model\job;


use cafett\GameTypeList;
use cafett\item\Bloodbag;
use game_chef\api\GameChef;
use game_chef\TaskSchedulerStorage;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;

class Healer extends Job
{
    const NAME = "Handyman";
    const DESCRIPTION = "";

    public function __construct() {
        parent::__construct(
            [
                new Bloodbag(),
                Item::get(ItemIds::WOODEN_SWORD),
                Item::get(ItemIds::WOODEN_PICKAXE),
                Item::get(ItemIds::WOODEN_AXE),
            ],
            [
                new EffectInstance(Effect::getEffect(Effect::WEAKNESS), null, 1)
            ],
            0
        );
    }

    public function activateByRightClick(Player $player): void {
        $this->initialSkillCoolTime = 15;
        $result = $this->activateSkill($player);
        if (!$result) return;

        $playerData = GameChef::findPlayerData($player->getName());

        $gamePlayers = $player->getLevel()->getPlayers();
        usort($gamePlayers, fn($a, $b) => $this->compare($player->asVector3(), $a, $b));

        $count = 0;
        foreach ($gamePlayers as $gamePlayer) {
            //クリエイティブ状態のOPとかを排除するため
            if (GameChef::isRelatedWith($gamePlayer, GameTypeList::core())) {
                $gamePlayerData = GameChef::findPlayerData($gamePlayer->getName());
                if ($gamePlayerData->getBelongTeamId()->equals($playerData->getBelongTeamId())) {
                    $gamePlayer->addEffect(new EffectInstance(Effect::getEffect(Effect::REGENERATION), 20 * 3, 3));
                    $count++;
                    if ($count === 3) return;
                }
            }
        }
    }

    public function activateByLeftClick(Player $player, Player $target): void {
        $this->initialSkillCoolTime = 45;
        $result = $this->activateSkill($player);
        if (!$result) return;
        if ($target->distance($player) > 10) return;

        if (GameChef::isRelatedWith($target, GameTypeList::core())) {
            $playerData = GameChef::findPlayerData($player->getName());
            $targetData = GameChef::findPlayerData($target->getName());
            if ($targetData->getBelongTeamId()->equals($playerData->getBelongTeamId())) {
                $target->setHealth($target->getHealth() + 15);
                //todo:パーティクル
            }
        }
    }

    //small to large
    private function compare(Vector3 $center, Player $a, Player $b): int {
        if ($a->distance($center) === $b->distance($center)) {
            return 0;
        }
        return ($a->distance($center) > $b->distance($center)) ? -1 : 1;
    }
}