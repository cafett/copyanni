<?php


namespace copyanni\model\job;


use copyanni\GameTypeList;
use copyanni\item\Bloodbag;
use game_chef\api\GameChef;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Healer extends Job
{
    const NAME = "Healer";
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

    public function activateByRightClick(Player $player): bool {
        $this->initialSkillCoolTime = 15;
        $result = $this->activateSkill($player);
        if (!$result) return false;

        $playerData = GameChef::findPlayerData($player->getName());

        $gamePlayers = $player->getLevel()->getPlayers();
        usort($gamePlayers, fn($a, $b) => $this->compare($player->asVector3(), $a, $b));

        $count = 0;
        foreach ($gamePlayers as $gamePlayer) {
            //クリエイティブ状態のOPとかを排除するため
            if (GameChef::isRelatedWith($gamePlayer, GameTypeList::anni())) {
                $gamePlayerData = GameChef::findPlayerData($gamePlayer->getName());
                if ($gamePlayerData->getBelongTeamId()->equals($playerData->getBelongTeamId())) {
                    $gamePlayer->addEffect(new EffectInstance(Effect::getEffect(Effect::REGENERATION), 20 * 3, 3));
                    $count++;
                    if ($count === 3) break;
                }
            }
        }

        return $count === 3;
    }

    public function activateByLeftClick(Player $player, Player $target): bool {
        $this->initialSkillCoolTime = 45;
        $result = $this->activateSkill($player);
        if (!$result) return false;
        if ($target->distance($player) > 10) return false;

        if (GameChef::isRelatedWith($target, GameTypeList::anni())) {
            $playerData = GameChef::findPlayerData($player->getName());
            $targetData = GameChef::findPlayerData($target->getName());
            if ($targetData->getBelongTeamId()->equals($playerData->getBelongTeamId())) {
                $target->setHealth($target->getHealth() + 15);
                //todo:パーティクル
                return true;
            }
        }

        return false;
    }

    //small to large
    private function compare(Vector3 $center, Player $a, Player $b): int {
        if ($a->distance($center) === $b->distance($center)) {
            return 0;
        }
        return ($a->distance($center) > $b->distance($center)) ? -1 : 1;
    }
}