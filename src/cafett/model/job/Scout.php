<?php


namespace cafett\model\job;


use cafett\entity\FishingHook;
use cafett\item\Grapple;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\Player;

class Scout extends Job
{
    const NAME = "Scout";
    const DESCRIPTION = "";

    public function __construct() {
        parent::__construct(
            [
                new Grapple(),
                Item::get(ItemIds::GOLDEN_SWORD),
                Item::get(ItemIds::WOODEN_PICKAXE),
                Item::get(ItemIds::WOODEN_AXE),
            ],
            [
                new EffectInstance(Effect::getEffect(Effect::SPEED), null, 1)
            ],
            2.5,
            0.5
        );
    }

    public function activateSkill(Player $player): bool {
        $result = parent::activateSkill($player);
        if (!$result) return false;

        $item = $player->getInventory()->getItemInHand();
        if (!$item->getNamedTag()->offsetExists("hook_id")) {
            $this->spawnFishingHook($player);
            return true;
        }

        $hook = $player->getLevel()->getEntity($item->getNamedTag()->getInt("hook_id"));
        if ($hook === null) {
            $this->spawnFishingHook($player);
            return true;
        }

        //引っ張られる
        $hook->kill();
        $vector3 = $hook->asVector3()->subtract($player->asVector3());
        $vector3 = $vector3->normalize()->multiply(3);
        $player->setMotion($vector3);
        return true;
    }

    private function spawnFishingHook(Player $player) {
        $nbt = Entity::createBaseNBT($player->getPosition()->add(0, 1, 0), $player->getDirectionVector()->multiply(1.2));
        $entity = new FishingHook($player->getLevel(), $nbt, $player);

        $item = $player->getInventory()->getItemInHand();
        $item->getNamedTag()->setInt("hook_id", $entity->getId());

        $entity->spawnToAll();
        $player->getInventory()->setItem($player->getInventory()->getHeldItemIndex(), $item);
    }
}