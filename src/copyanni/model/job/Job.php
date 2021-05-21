<?php


namespace copyanni\model\job;


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
    protected float $initialSkillCoolTime;
    protected float $skillCoolTime;
    protected float $skillCoolTimePeriod;


    public function __construct(array $initialInventory, array $effects, float $initialSkillCoolTime, float $skillCoolTimePeriod = 1) {
        $this->initialInventory = $initialInventory;
        $this->effects = $effects;
        $this->initialSkillCoolTime = $initialSkillCoolTime;
        $this->skillCoolTime = $initialSkillCoolTime;
        $this->skillCoolTimePeriod = $skillCoolTimePeriod;
    }

    public function activateSkill(Player $player): bool {
        if ($this->onCoolTime) {
            $player->sendMessage("あと" . $this->skillCoolTime . "秒");
            return false;
        } else {
            $this->onCoolTime = true;
            $this->skillCoolTime = $this->initialSkillCoolTime;
            $this->coolTimeHandler = TaskSchedulerStorage::get()->scheduleDelayedRepeatingTask(new ClosureTask(function (int $tick): void {
                $this->skillCoolTime -= $this->skillCoolTimePeriod;
                if ($this->skillCoolTime <= 0) {
                    $this->onCoolTime = false;
                    $this->coolTimeHandler->cancel();
                }
            }), 20 * $this->skillCoolTimePeriod, 20 * $this->skillCoolTimePeriod);
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

    //todo 職業変更時に呼び出す
    public function onChangeJob(): void { }
}