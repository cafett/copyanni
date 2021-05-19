<?php


namespace cafett\listener;

use cafett\block\Nexus;
use cafett\block\Ore;
use cafett\model\job\Archer;
use cafett\model\job\Warrior;
use cafett\scoreboard\CoreGameScoreboard;
use cafett\service\CoreGameService;
use cafett\GameTypeList;
use cafett\storage\CoreGamePlayerDataStorage;
use game_chef\api\GameChef;
use game_chef\models\GameStatus;
use game_chef\models\Score;
use game_chef\pmmp\bossbar\Bossbar;
use game_chef\pmmp\events\AddScoreEvent;
use game_chef\pmmp\events\FinishedGameEvent;
use game_chef\pmmp\events\PlayerAttackPlayerEvent;
use game_chef\pmmp\events\PlayerJoinGameEvent;
use game_chef\pmmp\events\PlayerKilledPlayerEvent;
use game_chef\pmmp\events\PlayerQuitGameEvent;
use game_chef\pmmp\events\StartedGameEvent;
use game_chef\pmmp\events\UpdatedGameTimerEvent;
use game_chef\services\MapService;
use game_chef\TaskSchedulerStorage;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class CoreGameListener implements Listener
{
    private TaskScheduler $scheduler;

    public function __construct(TaskScheduler $scheduler) {
        $this->scheduler = $scheduler;
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        CoreGameService::backToLobby($player);
    }

    public function onQuitGame(PlayerQuitGameEvent $event) {
        $player = $event->getPlayer();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::core())) return;

        CoreGameService::backToLobby($player);
    }

    public function onUpdatedGameTimer(UpdatedGameTimerEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::core())) return;

        //ボスバーの更新
        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
            $bossbar = Bossbar::findByType($player, GameTypeList::core()->toBossbarType());

            //ボスバーの無い試合 or バグ
            //ほぼ１００％前者なので処理を終わらせる
            if ($bossbar === null) return;

            if ($event->getTimeLimit() === null) {
                $bossbar->updateTitle("経過時間:({$event->getElapsedTime()})");
            } else {
                $bossbar->updateTitle("{$event->getElapsedTime()}/{$event->getTimeLimit()}");
                $bossbar->updatePercentage(1 - ($event->getElapsedTime() / $event->getTimeLimit()));
            }
        }
    }

    public function onStartedGame(StartedGameEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::core())) return;

        $game = GameChef::findTeamGameById($gameId);
        GameChef::setTeamGamePlayersSpawnPoint($gameId);

        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
            CoreGameService::sendToGame($player, $game);
        }
    }

    public function onFinishedGame(FinishedGameEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::core())) return;

        $winTeam = null;
        $availableTeamCount = 0;
        $game = GameChef::findGameById($gameId);
        foreach ($game->getTeams() as $team) {
            if ($team->getScore()->getValue() < Nexus::MAX_HEALTH) {
                $availableTeamCount++;
                $winTeam = $team;
            }
        }

        //2チーム以上残っていたら試合は終了しない
        if ($availableTeamCount >= 2) return;

        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
            if ($player === null) continue;

            $player->sendMessage($winTeam->getTeamColorFormat() . $winTeam->getName() . TextFormat::RESET . "の勝利！！！");
            $player->sendMessage("10秒後にロビーに戻ります");
        }

        $this->scheduler->scheduleDelayedTask(new ClosureTask(function (int $tick) use ($gameId) : void {
            //10秒間で退出する可能性があるから、foreachをもう一度書く
            //上で１プレイヤーずつタスクを書くこともできるが流れがわかりやすいのでこうしている
            foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
                $player = Server::getInstance()->getPlayer($playerData->getName());
                if ($player === null) continue;

                CoreGameService::backToLobby($player);
            }

            GameChef::discardGame($gameId);
        }), 20 * 10);
    }

    public function onPlayerJoinedGame(PlayerJoinGameEvent $event) {
        $player = $event->getPlayer();
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        $teamId = $event->getTeamId();
        if (!$gameType->equals(GameTypeList::core())) return;

        $game = GameChef::findGameById($gameId);
        $team = $game->getTeamById($teamId);

        //メッセージ
        foreach (GameChef::getPlayerDataList($gameId) as $gamePlayerData) {
            $gamePlayer = Server::getInstance()->getPlayer($gamePlayerData->getName());
            if ($gamePlayer === null) continue;
            $gamePlayer->sendMessage("{$player->getName()}が" . $team->getTeamColorFormat() . $team->getName() . TextFormat::RESET . "に参加しました");
        }

        $player->sendMessage($team->getTeamColorFormat() . $team->getName() . TextFormat::RESET . "に参加しました");

        //途中参加
        $game = GameChef::findTeamGameById($gameId);
        if ($game->getStatus()->equals(GameStatus::Started())) {
            CoreGameService::sendToGame($player, $game);
        }
    }

    public function onPlayerKilledPlayer(PlayerKilledPlayerEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        $attacker = $event->getAttacker();
        $killedPlayer = $event->getKilledPlayer();

        if (!$gameType->equals(GameTypeList::core())) return;
        if ($event->isFriendlyFire()) return;//試合の設定上ありえないけど

        $game = GameChef::findTeamGameById($gameId);

        $attackerData = GameChef::findPlayerData($attacker->getName());
        $attackerTeam = $game->findTeamById($attackerData->getBelongTeamId());

        $killedPlayerData = GameChef::findPlayerData($killedPlayer->getName());
        $killedPlayerTeam = $game->findTeamById($killedPlayerData->getBelongTeamId());

        //メッセージを送信
        $message = $attackerTeam->getTeamColorFormat() . "[{$attacker->getName()}]" . TextFormat::RESET .
            " killed" .
            $killedPlayerTeam->getTeamColorFormat() . " [{$killedPlayer->getName()}]";
        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $gamePlayer = Server::getInstance()->getPlayer($playerData->getName());
            $gamePlayer->sendMessage($message);
        }
    }

    public function onAddedScore(AddScoreEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::core())) return;

        $game = GameChef::findTeamGameById($gameId);
        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
            CoreGameScoreboard::update($player, $game);
        }
    }

    public function onPlayerDeath(PlayerDeathEvent $event) {
        $player = $event->getPlayer();
        if (!GameChef::isRelatedWith($player, GameTypeList::core())) return;

        $playerData = GameChef::findPlayerData($player->getName());
        $game = GameChef::findGameById($playerData->getBelongGameId());
        $team = $game->getTeamById($playerData->getBelongTeamId());
        if ($team->getScore()->getValue() === Nexus::MAX_HEALTH) {
            GameChef::quitGame($player);
        } else {
            //スポーン地点を再設定
            GameChef::setTeamGamePlayerSpawnPoint($event->getPlayer());
        }
    }

    public function onPlayerReSpawn(PlayerRespawnEvent $event) {
        $player = $event->getPlayer();
        if (!GameChef::isRelatedWith($player, GameTypeList::core())) return;

        CoreGameService::initPlayerStatus($player);
    }

    public function onBreakNexus(BlockBreakEvent $event) {
        $block = $event->getBlock();

        $player = $event->getPlayer();
        $level = $player->getLevel();

        //試合中のマップじゃなかったら
        if (!MapService::isInstantWorld($level->getName())) return;

        //プレイヤーが試合に参加していなかったら
        $playerData = GameChef::findPlayerData($player->getName());
        if ($playerData->getBelongGameId() === null) return;//eventをキャンセルしてもいい

        $game = GameChef::findGameById($playerData->getBelongGameId());

        //core game じゃなかったら
        if (!$game->getType()->equals(GameTypeList::core())) return;

        //nexus
        if ($block->getId() === Nexus::ID) {
            $targetTeam = null;
            foreach ($game->getTeams() as $team) {
                $teamNexusVector = $team->getCustomVectorData(Nexus::POSITION_DATA_KEY);
                if ($block->asVector3()->equals($teamNexusVector)) {
                    $targetTeam = $team;
                }
            }

            if ($targetTeam === null) throw new \UnexpectedValueException("そのネクサスを持つチームが存在しませんでした");

            //自軍のネクサスだったら
            if ($targetTeam->getId()->equals($playerData->getBelongTeamId())) {
                $event->setCancelled();
                $player->sendTip("自軍のネクサスを破壊することはできません");
                return;
            }

            //すでに死んだチームなら(ネクサスを置き換えるからありえないけど)
            if ($targetTeam->getScore()->isBiggerThan(new Score(Nexus::MAX_HEALTH))) {
                $event->setCancelled();
                return;
            }

            $event->setDrops([]);
            CoreGameService::breakNexus($game, $targetTeam, $player, $block->asVector3());
        } else if (in_array($block->getId(), Ore::IDS)) {
            TaskSchedulerStorage::get()->scheduleDelayedTask(new ClosureTask(function (int $tick) use ($block): void {
                $block->getLevel()->setBlock($block->asVector3(), $block);
            }), Ore::GENERATING_COOL_TIMES[$block->getId()]);
        }
    }

    public function onPlaceBlock(BlockPlaceEvent $event) {
        $player = $event->getPlayer();
        $level = $player->getLevel();

        if (!MapService::isInstantWorld($level->getName())) return;

        $playerData = GameChef::findPlayerData($player->getName());
        if ($playerData->getBelongGameId() === null) return;

        $game = GameChef::findGameById($playerData->getBelongGameId());
        if (!$game->getType()->equals(GameTypeList::core())) return;

        if (!$this->isCanPlace($event->getBlock()->getId())) {
            if (!$player->isOp() and $player->getGamemode() !== Player::CREATIVE) {
                $event->setCancelled();
                $player->sendTip("you cannot place this block");
            }
        }
    }

    private function isCanPlace(int $id): bool {
        if (in_array($id, Ore::IDS)) return false;
        if ($id === Nexus::ID) return false;
        return true;
    }

    public function onDamage(EntityShootBowEvent $event) {
        $arrow = $event->getEntity();

        //職業がArcherなら、与えるダメージ + 1
        if ($arrow instanceof \pocketmine\entity\projectile\Arrow) {
            $shooter = $arrow->getOwningEntity();
            if ($shooter instanceof Player) {
                $shooterData = CoreGamePlayerDataStorage::get($shooter);
                if ($shooterData->getCurrentJob()::NAME === Archer::NAME) {
                    $arrow->setBaseDamage($arrow->getBaseDamage() + 1);
                }
            }
        }
    }

    public function onPlayerAttackPlayer(PlayerAttackPlayerEvent $event) {
        if (!$event->getGameType()->equals(GameTypeList::core())) return;

        $attacker = $event->getAttacker();
        $attackerData = CoreGamePlayerDataStorage::get($attacker->getName());
        $job = $attackerData->getCurrentJob();
        //Warriorならダメージ+1, Skill発動中ならさらに+1
        if ($job instanceof Warrior) {
            $additionalDamage = 1;
            if ($job->isOnFrenzy()) $additionalDamage++;

            $target = $event->getTarget();
            $source = new EntityDamageByEntityEvent($attacker, $target, $event->getCause(), $event->getBaseDamage(), [], $event->getKnockBack());
            $target->setLastDamageCause($source);
            $target->setHealth($target->getHealth() - $additionalDamage);
        }
    }
}