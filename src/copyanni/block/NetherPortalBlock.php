<?php


namespace copyanni\block;


use copyanni\model\JobChanger;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\entity\Entity;
use pocketmine\Player;

class NetherPortalBlock extends Block
{
    const ID = BlockIds::PORTAL;

    public function __construct(int $meta = 0) {
        parent::__construct(self::ID, $meta, "", self::ID);
    }

    public function getName(): string {
        return "Portal";
    }

    public function onEntityCollide(Entity $entity): void {
        if ($entity instanceof Player) {
            JobChanger::startLoad($entity);
        }
    }
}