<?php


namespace cafett\model\job;


use cafett\item\ResourceDrop;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\Player;

//todo : Delaying Blockの実装
class Builder extends Job
{
    const NAME = "Builder";
    const DESCRIPTION = "";

    public function __construct() {
        parent::__construct(
            [
                new ResourceDrop(),
                Item::get(ItemIds::WOODEN_SWORD),
                Item::get(ItemIds::WOODEN_PICKAXE),
                Item::get(ItemIds::WOODEN_AXE),
                Item::get(ItemIds::WOODEN_SHOVEL),
            ],
            [],
            90
        );
    }

    /*
     * todo:
     * 鉄格子5～9個
     * マツのフェンス0～9個
     * マツの木の階段0～14個
     * 松明2～3個
     */
    public function activateSkill(Player $player): bool {
        $result = parent::activateSkill($player);
        $items = [
            Item::get(ItemIds::DIRT, 0, mt_rand(33, 58)),
            Item::get(ItemIds::BRICK_BLOCK, 0, mt_rand(0, 36)),
            Item::get(ItemIds::STONE, 0, mt_rand(25, 64)),
            Item::get(ItemIds::WOOD, 0, mt_rand(0, 64)),
            Item::get(ItemIds::WOOL, 0, mt_rand(18, 28)),
            Item::get(ItemIds::GLASS, 0, mt_rand(0, 19)),
        ];

        if ($result) $player->getInventory()->addItem($items);

        return $result;
    }
}