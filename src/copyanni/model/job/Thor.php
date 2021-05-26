<?php


namespace copyanni\model\job;


use copyanni\item\FireStorm;
use copyanni\item\Hammer;
use game_chef\api\GameChef;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\Player;

class Thor extends Job
{
    const NAME = "Thor";
    const DESCRIPTION = "";

    public function __construct() {
        parent::__construct(
            [
                new Hammer(),
                Item::get(ItemIds::WOODEN_SWORD),
                Item::get(ItemIds::WOODEN_PICKAXE),
                Item::get(ItemIds::WOODEN_AXE),
            ],
            [
                new EffectInstance(Effect::getEffect(Effect::RESISTANCE))
            ],
            45
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