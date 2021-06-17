<?php


namespace copyanni\model\job;


use copyanni\block\Ore;
use copyanni\block\PortalBlock;
use copyanni\model\Portal;
use copyanni\storage\PortalStorage;
use game_chef\api\GameChef;
use game_chef\TaskSchedulerStorage;
use pocketmine\block\Block;
use pocketmine\level\particle\SmokeParticle;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;

class Transporter extends Job
{
    const NAME = "Transporter";
    const DESCRIPTION = "";

    private ?int $pos1BlockId = null;
    private ?Vector3 $pos1 = null;
    private ?int $pos2BlockId = null;
    private ?Vector3 $pos2 = null;

    private ?TaskHandler $handler = null;

    public function __construct() {
        parent::__construct(
            [

            ],
            [
            ],
            0
        );
    }

    public function activateSkill(Player $player, ?Block $block = null): bool {
        if ($block === null) $this->resetPortal($player);

        if (!$this->canSetPortal($block)) {
            $player->sendTip("そのブロックにポータルを置くことはできません");
            return false;
        }

        if ($this->pos1 === null) {
            $this->pos1 = $block->asVector3();
            $this->pos1BlockId = $block->getId();

            $level = $player->getLevel();
            $level->setBlock($this->pos1, Block::get(PortalBlock::ID));
            $this->handler = TaskSchedulerStorage::get()->scheduleRepeatingTask(new ClosureTask(function (int $tick) use ($level) : void {
                $level->addParticle(new SmokeParticle($this->pos1));
            }), 20 * 1);
            return true;
        } else if ($this->pos2 === null) {
            $this->pos2 = $block->asVector3();
            $this->pos2BlockId = $block->getId();

            $level = $player->getLevel();
            $level->setBlock($this->pos2, Block::get(PortalBlock::ID));
            $this->handler = TaskSchedulerStorage::get()->scheduleRepeatingTask(new ClosureTask(function (int $tick) use ($level) : void {
                $level->addParticle(new SmokeParticle($this->pos1));
                $level->addParticle(new SmokeParticle($this->pos2));
            }), 20 * 1);

            $playerData = GameChef::findPlayerData($player->getName());
            PortalStorage::add(new Portal($playerData->getBelongGameId(), $playerData->getBelongTeamId(), $this->pos1, $this->pos2));
            return true;
        }
        return false;
    }

    private function canSetPortal(Block $block): bool {
        //todo:パーミッション内もダメ
        if (in_array($block->getId(), Ore::IDS)) return false;
        return true;
    }

    public function resetPortal(Player $player): void {
        $level = $player->getLevel();
        $level->setBlock($this->pos1, Block::get($this->pos1BlockId));
        $level->setBlock($this->pos2, Block::get($this->pos2BlockId));

        if ($this->handler !== null) $this->handler->cancel();

        $playerData = GameChef::findPlayerData($player->getName());
        PortalStorage::delete(new Portal($playerData->getBelongGameId(), $playerData->getBelongTeamId(), $this->pos1, $this->pos2));

        $this->pos1 = null;
        $this->pos2 = null;
        $this->pos1BlockId = null;
        $this->pos2BlockId = null;
    }

    public function onChangeJob(Player $player): void {
        $this->resetPortal($player);
    }

    /**
     * @return Vector3|null
     */
    public function getPos1(): ?Vector3 {
        return $this->pos1;
    }

    /**
     * @return Vector3|null
     */
    public function getPos2(): ?Vector3 {
        return $this->pos2;
    }

    public function onQuitGame(Player $player): void {
        $this->resetPortal($player);
    }
}