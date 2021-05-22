<?php


namespace copyanni\model\job;


use copyanni\block\Nexus;
use copyanni\item\GuardiansWarp;
use game_chef\api\GameChef;
use game_chef\models\Score;
use game_chef\TaskSchedulerStorage;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;

class Defender extends Job
{
    const NAME = "Defender";
    const DESCRIPTION = "";
    const TELEPORT_DIRECTION_KEY = "defender_position";

    private TaskHandler $handler;

    public function __construct() {
        parent::__construct(
            [
                new GuardiansWarp(),
                Item::get(ItemIds::CHAIN_CHESTPLATE),
                Item::get(ItemIds::WOODEN_SWORD),
                Item::get(ItemIds::WOODEN_PICKAXE),
                Item::get(ItemIds::WOODEN_AXE),
                Item::get(ItemIds::WOODEN_SHOVEL),
            ],
            [],
            20
        );
    }


    public function activatePassive(Player $player): void {
        parent::activatePassive($player);

        $playerData = GameChef::findPlayerData($player->getName());
        $game = GameChef::findGameById($playerData->getBelongGameId());
        $team = $game->getTeamById($playerData->getBelongTeamId());
        $health = $this->calculateHealth($team->getScore());
        $player->setMaxHealth($health);

        $nexusPosition = $team->getCustomVectorData(Nexus::POSITION_DATA_KEY);
        $playerName = $player->getName();
        $this->handler = TaskSchedulerStorage::get()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($playerName, $nexusPosition): void {
            $player = Server::getInstance()->getPlayer($playerName);
            if ($player === null) return;
            if (!$player->isAlive()) return;

            if ($nexusPosition->distance($player) <= 50) {

                $player->addEffect(new EffectInstance(Effect::getEffect(Effect::REGENERATION), 20 * 2, 1));
            }
        }), 20 * 2);
    }


    public function activateSkill(Player $player): bool {
        $result = parent::activateSkill($player);
        if (!$result) return false;

        $playerData = GameChef::findPlayerData($player->getName());
        $game = GameChef::findGameById($playerData->getBelongGameId());
        $team = $game->getTeamById($playerData->getBelongTeamId());
        $nexusPosition = $team->getCustomVectorData(Nexus::POSITION_DATA_KEY);
        if ($nexusPosition->distance($player) <= 50) {
            $defenderPosition = $team->getCustomVectorData(self::TELEPORT_DIRECTION_KEY);
            $player->teleport($defenderPosition);
        }

        return true;
    }

    public function onChangeJob(Player $player): void {
        if ($this->handler !== null) {
            $this->handler->cancel();
            $player->setMaxHealth(20);
        }
    }

    private function calculateHealth(Score $score): int {
        $value = $score->getValue();
        if ($value < 0) return 20;

        return 20 + ceil($value/10);
    }

    public function updateMaxHealth(Player $player) {
        $playerData = GameChef::findPlayerData($player->getName());
        $game = GameChef::findGameById($playerData->getBelongGameId());
        $team = $game->getTeamById($playerData->getBelongTeamId());
        $player->setMaxHealth($this->calculateHealth($team->getScore()));
    }
}