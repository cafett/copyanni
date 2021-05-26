<?php


namespace copyanni\model\job;


use copyanni\item\FireStorm;
use copyanni\item\Hammer;
use copyanni\service\SoundService;
use game_chef\api\GameChef;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityIds;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\Player;
use pocketmine\Server;

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
        foreach ($targets as $target) {
            $light = new AddActorPacket();
            $light->type = "minecraft:lightning_bolt";
            $light->entityRuntimeId = Entity::$entityCount++;
            $light->metadata = [];
            $light->motion = null;
            $light->yaw = $player->getYaw();
            $light->pitch = $player->getPitch();
            $light->position = new Vector3($player->getX(), $player->getY(), $player->getZ());
            Server::getInstance()->broadcastPacket([$target, $player], $light);

            //eventはcallしなくていい
            $target->setHealth($target->getHealth() - 5);
            $target->setLastDamageCause(new EntityDamageByEntityEvent($player, $target, EntityDamageEvent::CAUSE_MAGIC, 5));

            SoundService::play($target, $target, "ambient.weather.thunder", 1, 1);
            SoundService::play($player, $target, "ambient.weather.thunder", 1, 1);
        }
        return $result;
    }
}