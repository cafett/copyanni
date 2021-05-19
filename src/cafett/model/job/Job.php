<?php


namespace cafett\model\job;


use game_chef\TaskSchedulerStorage;
use pocketmine\entity\EffectInstance;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;

abstract class Job
{
    const NAME = "";
    const DESCRIPTION = "";

    protected array $initialInventory;
    protected array $effects;

    protected TaskHandler $coolTimeHandler;
    protected bool $onCoolTime = false;
    protected int $initialSkillCoolTime;
    protected int $skillCoolTime;

    public function __construct(array $initialInventory, array $effects, int $initialSkillCoolTime) {
        $this->initialInventory = $initialInventory;
        $this->effects = $effects;
        $this->initialSkillCoolTime = $initialSkillCoolTime;
        $this->skillCoolTime = $initialSkillCoolTime;
    }

    public function activateSkill(Player $player): bool {
        if ($this->onCoolTime) {
            $player->sendMessage("あと" . $this->skillCoolTime . "秒");
            return false;
        } else {
            $this->coolTimeHandler = TaskSchedulerStorage::get()->scheduleDelayedRepeatingTask(new ClosureTask(function (int $tick) {
                $this->skillCoolTime--;
                if ($this->skillCoolTime === 0) {
                    $this->onCoolTime = false;
                    $this->coolTimeHandler->cancel();
                }
            }), 20, 20);
            return true;
        }
    }

    /**
     * @return Item[]
     */
    public function getInitialInventory(): array {
        return $this->initialInventory;
    }

    /**
     * @return EffectInstance[]
     */
    public function getEffects(): array {
        return $this->effects;
    }
}