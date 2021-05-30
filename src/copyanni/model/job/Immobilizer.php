<?php


namespace copyanni\model\job;


use copyanni\GameTypeList;
use copyanni\storage\AnniPlayerDataStorage;
use game_chef\api\GameChef;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\Player;

class Immobilizer extends Job
{
    const NAME = "Immobilizer";
    const DESCRIPTION = "";

    public function __construct() {
        parent::__construct(
            [
                Item::get(ItemIds::WOODEN_SWORD),
                Item::get(ItemIds::WOODEN_PICKAXE),
                Item::get(ItemIds::WOODEN_AXE),
            ],
            [],
            60
        );
    }

    public function activateSkill(Player $player): bool {
        $target = null;
        $distance = 6;;

        $playerData = GameChef::findPlayerData($player->getName());
        foreach ($player->getLevel()->getPlayers() as $subject) {
            if ($subject->distance($player) <= $distance) {
                $target = $subject;
                if (GameChef::isRelatedWith($subject, GameTypeList::anni())) {
                    $subjectData = GameChef::findPlayerData($subject->getName());

                    if (!$subjectData->getBelongTeamId()->equals($playerData->getBelongTeamId())) {//チーム判定
                        $subjectAnniData = AnniPlayerDataStorage::get($subject->getName());
                        $lastTime = $subjectAnniData->getLastImmobilizedTime();

                        if ($lastTime === null) {//最後に喰らってから15秒以上たっていればOK
                            $distance = $subject->distance($player);
                            $target = $subject;
                            $subjectAnniData->setLastImmobilizedTime(new \DateTime());
                        } else if ((new \DateTime())->diff($lastTime)->s >= 15) {
                            $distance = $subject->distance($player);
                            $target = $subject;
                            $subjectAnniData->setLastImmobilizedTime(new \DateTime());
                        }
                    }
                }
            }
        }
        if ($target === null) return false;

        $result = parent::activateSkill($player);
        if (!$result) return false;
        $target->addEffect(new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 20 * 4, 10));
        $target->addEffect(new EffectInstance(Effect::getEffect(Effect::JUMP_BOOST), 20 * 4, -10));
        $target->addEffect(new EffectInstance(Effect::getEffect(Effect::ABSORPTION), 20 * 4.5, 1));
        $target->addEffect(new EffectInstance(Effect::getEffect(Effect::MINING_FATIGUE), 20 * 5, 2));

        $player->addEffect(new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 20 * 4, 10));
        $player->addEffect(new EffectInstance(Effect::getEffect(Effect::JUMP_BOOST), 20 * 4, -10));
        //todo:パーティクル
        return true;
    }
}