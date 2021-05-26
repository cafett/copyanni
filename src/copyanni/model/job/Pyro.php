<?php


namespace copyanni\model\job;


use copyanni\item\FireStorm;
use game_chef\api\GameChef;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\Player;

class Pyro extends Job
{
    const NAME = "Pyro";
    const DESCRIPTION = "";

    public function __construct() {
        parent::__construct(
            [
                new FireStorm(),
                Item::get(ItemIds::POTION, 21, 1),
                Item::get(ItemIds::STONE_SWORD),
                Item::get(ItemIds::WOODEN_PICKAXE),
                Item::get(ItemIds::WOODEN_AXE),
            ],
            [
                new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE))
            ],
            40
        );
    }

    public function activateSkill(Player $player): bool {
        $result = parent::activateSkill($player);
        if (!$result) return false;

        $playerData = GameChef::findPlayerData($player->getName());
        $targets = GameChef::getAroundEnemies($playerData->getBelongGameId(), [$playerData->getBelongTeamId()], $player, 4);
        foreach ($targets as $target) $target->setOnFire(6);
        return $result;
    }
}