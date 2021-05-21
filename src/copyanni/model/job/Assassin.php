<?php


namespace copyanni\model\job;


use copyanni\item\Leap;
use game_chef\TaskSchedulerStorage;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;

class Assassin extends Job
{
    const NAME = "Assassin";
    const DESCRIPTION = "";

    private bool $isOnLeap = false;
    private array $contents = [];
    private TaskHandler $handler;

    public function __construct() {
        parent::__construct(
            [
                new Leap(),
                Item::get(ItemIds::WOODEN_SWORD),
                Item::get(ItemIds::WOODEN_PICKAXE),
                Item::get(ItemIds::WOODEN_AXE),
                Item::get(ItemIds::WOODEN_SHOVEL),
            ],
            [],
            40
        );
    }

    public function activateSkill(Player $player): bool {
        //$result = parent::activateSkill($player);
        //if (!$result) return false;

        $this->contents = $player->getArmorInventory()->getContents();
        $player->getArmorInventory()->setContents([]);
        $player->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 20 * 6, 1, false));
        $player->addEffect(new EffectInstance(Effect::getEffect(Effect::INVISIBILITY), 20 * 6, 1, false));
        $player->knockBack($player, 0, $player->getDirectionVector()->getX(), $player->getDirectionVector()->getZ(), 1.4);

        $this->isOnLeap = true;
        $playerName = $player->getName();
        $this->handler = TaskSchedulerStorage::get()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($playerName): void {
            $this->reverseArmor($playerName);
        }), 20 * 6);
        return true;
    }

    public function isOnLeap(): bool {
        return $this->isOnLeap;
    }

    public function cancelSkill(): void {
        if ($this->handler !== null) {
            $this->handler->cancel();
            $this->isOnLeap = false;
        }
    }

    public function reverseArmor(string $playerName): void {
        if ($this->isOnLeap) {
            $player = Server::getInstance()->getPlayer($playerName);
            if ($player === null) return;
            if (!$player->isAlive()) return;
            $player->getArmorInventory()->setContents($this->contents);
            $this->isOnLeap = false;
        }
    }

    public function onChangeJob(): void {
        $this->cancelSkill();
    }
}