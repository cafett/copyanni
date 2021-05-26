<?php


namespace copyanni\model\job;


use copyanni\entity\ScorpioHookEntity;
use copyanni\item\Hook;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\Player;

class Scorpio extends Job
{
    const NAME = "Scorpio";
    const DESCRIPTION = "";

    public function __construct() {
        parent::__construct(
            [
                new Hook(),
                Item::get(ItemIds::STONE_SWORD),
                Item::get(ItemIds::WOODEN_PICKAXE),
                Item::get(ItemIds::WOODEN_AXE),
            ],
            [],
            4
        );
    }

    public function activateSkill(Player $player): bool {
        $result = parent::activateSkill($player);
        if (!$result) return false;

        $nbt = Entity::createBaseNBT($player->asVector3()->add(0, $player->getEyeHeight()), $player->getDirectionVector()->multiply(2), $player->getYaw(), $player->getPitch());

        $itemTag = Item::get(ItemIds::NETHER_STAR)->nbtSerialize();
        $itemTag->setName("Item");
        $nbt->setTag($itemTag);

        $entity = new ScorpioHookEntity($player->getLevel(), $nbt);
        $entity->setOwner($player->getName());

        $entity->spawnToAll();
        return $result;
    }
}