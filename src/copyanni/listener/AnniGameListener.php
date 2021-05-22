<?php


namespace copyanni\listener;

use copyanni\block\Nexus;
use copyanni\block\Ore;
use copyanni\model\job\Acrobat;
use copyanni\model\job\Archer;
use copyanni\model\job\Assassin;
use copyanni\model\job\Defender;
use copyanni\model\job\Warrior;
use copyanni\scoreboard\AnniGameScoreboard;
use copyanni\service\AnniGameService;
use copyanni\GameTypeList;
use copyanni\storage\AnniPlayerDataStorage;
use game_chef\api\GameChef;
use game_chef\models\GameStatus;
use game_chef\models\Score;
use game_chef\pmmp\bossbar\Bossbar;
use game_chef\pmmp\events\AddedScoreEvent;
use game_chef\pmmp\events\FinishedGameEvent;
use game_chef\pmmp\events\PlayerAttackPlayerEvent;
use game_chef\pmmp\events\PlayerJoinGameEvent;
use game_chef\pmmp\events\PlayerKilledPlayerEvent;
use game_chef\pmmp\events\PlayerQuitGameEvent;
use game_chef\pmmp\events\StartedGameEvent;
use game_chef\pmmp\events\UpdatedGameTimerEvent;
use game_chef\services\MapService;
use game_chef\TaskSchedulerStorage;
use pocketmine\block\Bedrock;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerToggleFlightEvent;
use pocketmine\level\particle\ExplodeParticle;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class AnniGameListener implements Listener
{
    private TaskScheduler $scheduler;

    public function __construct(TaskScheduler $scheduler) {
        $this->scheduler = $scheduler;
    }

    public function onJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        AnniGameService::backToLobby($player);
    }

    public function onQuitGame(PlayerQuitGameEvent $event) {
        $player = $event->getPlayer();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::anni())) return;

        AnniGameService::backToLobby($player);
    }

    public function onUpdatedGameTimer(UpdatedGameTimerEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::anni())) return;

        $phase = AnniGameService::getGamePhase($gameId);
        //ボスバーの更新
        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
            $bossbar = Bossbar::findByType($player, GameTypeList::anni()->toBossbarType());

            //ボスバーの無い試合 or バグ
            //ほぼ１００％前者なので処理を終わらせる
            if ($bossbar === null) return;

            $bossbar->updateTitle("Phase $phase");

            if ($phase === 1) {
                $bossbar->updatePercentage(1.0 - ($event->getElapsedTime() / 600));

            } else if ($phase >= 5) {
                $bossbar->updatePercentage(1.0);

            } else {
                $bossbar->updatePercentage(1.0 - (($event->getElapsedTime() - (600 * $phase)) / 600));
            }

            if ($event->getElapsedTime() % 60 === 0) {
                //todo:演出
                $player->sendMessage("phase$phase になりました");
            }
        }
    }

    public function onStartedGame(StartedGameEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::anni())) return;

        $game = GameChef::findTeamGameById($gameId);
        GameChef::setTeamGamePlayersSpawnPoint($gameId);

        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
            AnniGameService::sendToGame($player, $game);
        }
    }

    public function onFinishedGame(FinishedGameEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::anni())) return;

        $winTeam = null;
        $game = GameChef::findGameById($gameId);
        foreach ($game->getTeams() as $team) {
            if ($team->getScore()->getValue() < Nexus::MAX_HEALTH)  $winTeam = $team;
        }

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

                AnniGameService::backToLobby($player);
            }

            GameChef::discardGame($gameId);
        }), 20 * 10);
    }

    public function onPlayerJoinedGame(PlayerJoinGameEvent $event) {
        $player = $event->getPlayer();
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        $teamId = $event->getTeamId();
        if (!$gameType->equals(GameTypeList::anni())) return;

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
            AnniGameService::sendToGame($player, $game);
        }
    }

    public function onPlayerKilledPlayer(PlayerKilledPlayerEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        $attacker = $event->getAttacker();
        $killedPlayer = $event->getKilledPlayer();

        if (!$gameType->equals(GameTypeList::anni())) return;
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

    //Defender
    public function onAddedScore(AddedScoreEvent $event) {
        $gameId = $event->getGameId();
        $gameType = $event->getGameType();
        if (!$gameType->equals(GameTypeList::anni())) return;

        $value = $event->getCurrentScore()->getValue();
        if ($value === 1) {
            $isTimeToUpdateDefenderHealth = true;
        } else {
            $isTimeToUpdateDefenderHealth = ($value % 10 === 1);
        }

        $game = GameChef::findTeamGameById($gameId);
        foreach (GameChef::getPlayerDataList($gameId) as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());
            AnniGameScoreboard::update($player, $game);

            //Defender
            if ($isTimeToUpdateDefenderHealth) {
                $playerAnniData = AnniPlayerDataStorage::get($player->getName());
                $job = $playerAnniData->getCurrentJob();
                if ($job instanceof Defender) $job->updateMaxHealth($player);
            }
        }

        $teamId = $event->getTeamId();
        $team = $game->getTeamById($teamId);

        $isCritical = $event->getCurrentScore() === Nexus::MAX_HEALTH;
        $level = Server::getInstance()->getLevelByName($game->getMap()->getLevelName());
        $nexusPosition = $team->getCustomVectorData(Nexus::POSITION_DATA_KEY);
        if ($isCritical) {
            $level->setBlock($nexusPosition, new Bedrock());
            $i = 0;
            while ($i > 4) {
                $level->addParticle(new ExplodeParticle($nexusPosition));
                $i++;
            }

            $availableTeamCount = 0;
            $game = GameChef::findGameById($gameId);
            foreach ($game->getTeams() as $team) {
                if ($team->getScore()->getValue() < Nexus::MAX_HEALTH) {
                    $availableTeamCount++;
                }
            }
            //2チーム以上残っていたら試合は終了しない
            if ($availableTeamCount >= 2) return;
            GameChef::finishGame($gameId);
        } else {
            TaskSchedulerStorage::get()->scheduleDelayedTask(new ClosureTask(function (int $tick) use ($level, $nexusPosition) : void {
                $level->setBlock($nexusPosition, Block::get(Nexus::ID));
            }), 20 * 0.5);
        }
    }

    //Assassin
    public function onPlayerDeath(PlayerDeathEvent $event) {
        $player = $event->getPlayer();
        if (!GameChef::isRelatedWith($player, GameTypeList::anni())) return;

        $playerAnniData = AnniPlayerDataStorage::get($player->getName());
        $job = $playerAnniData->getCurrentJob();
        if ($job instanceof Assassin) $job->cancelSkill();

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
        if (!GameChef::isRelatedWith($player, GameTypeList::anni())) return;

        AnniGameService::initPlayerStatus($player);
    }

    public function onBreakBlock(BlockBreakEvent $event) {
        $block = $event->getBlock();

        $player = $event->getPlayer();
        $level = $player->getLevel();

        //試合中のマップじゃなかったら
        if (!MapService::isInstantWorld($level->getName())) return;

        //プレイヤーが試合に参加していなかったら
        $playerData = GameChef::findPlayerData($player->getName());
        if ($playerData->getBelongGameId() === null) return;//eventをキャンセルしてもいい

        $game = GameChef::findGameById($playerData->getBelongGameId());

        //anni じゃなかったら
        if (!$game->getType()->equals(GameTypeList::anni())) return;

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
            AnniGameService::breakNexus($game, $targetTeam, $player, $block->asVector3());
        } else if (in_array($block->getId(), Ore::IDS)) {
            //ore

            //diamondはphase3から
            if ($block->getId() === BlockIds::DIAMOND_ORE) {
                if (AnniGameService::getGamePhase($game->getId()) < 3) {
                    $event->setCancelled();
                    return;
                }
            }

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
        if (!$game->getType()->equals(GameTypeList::anni())) return;

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

    //Archer
    public function onDamageByShooting(EntityShootBowEvent $event) {
        $arrow = $event->getEntity();

        //職業がArcherなら、与えるダメージ + 1
        if ($arrow instanceof \pocketmine\entity\projectile\Arrow) {
            $shooter = $arrow->getOwningEntity();
            if ($shooter instanceof Player) {
                $shooterData = AnniPlayerDataStorage::get($shooter);
                if ($shooterData->getCurrentJob()::NAME === Archer::NAME) {
                    $arrow->setBaseDamage($arrow->getBaseDamage() + 1);
                }
            }
        }
    }

    //Warrior Assassin
    public function onPlayerAttackPlayer(PlayerAttackPlayerEvent $event) {
        if (!$event->getGameType()->equals(GameTypeList::anni())) return;

        $attacker = $event->getAttacker();
        $attackerData = AnniPlayerDataStorage::get($attacker->getName());
        $attackerJob = $attackerData->getCurrentJob();
        //Warriorならダメージ+1, Skill発動中ならさらに+1
        if ($attackerJob instanceof Warrior) {
            $additionalDamage = 1;
            if ($attackerJob->isOnFrenzy()) $additionalDamage++;

            $target = $event->getTarget();
            $source = new EntityDamageByEntityEvent($attacker, $target, $event->getCause(), $event->getBaseDamage(), [], $event->getKnockBack());
            $target->setLastDamageCause($source);
            $target->setHealth($target->getHealth() - $additionalDamage);
        }

        //Assassinならスキルをキャンセルする
        $targetJob = AnniPlayerDataStorage::get($attacker->getName())->getCurrentJob();
        if ($targetJob instanceof Assassin) {
            $targetJob->cancelSkill();
            $targetJob->reverseArmor($event->getTarget()->getName());
        }
    }

    //Acrobat
    public function onJump(PlayerJumpEvent $event) {
        $player = $event->getPlayer();

        $playerData = AnniPlayerDataStorage::get($player->getName());
        if ($playerData->getCurrentJob() instanceof Acrobat) {
            $player->setAllowFlight(true);
        }
    }

    //Acrobat
    public function onToggleFlight(PlayerToggleFlightEvent $event) {
        $player = $event->getPlayer();
        $playerData = AnniPlayerDataStorage::get($player->getName());
        if ($playerData->getCurrentJob() instanceof Acrobat) {
            $player->setFlying(false);
            $playerData->getCurrentJob()->activateSkill($player);
            $event->setCancelled(true);
        }
    }

    //Acrobat Assassin
    public function onFallDamage(EntityDamageEvent $event) {
        if ($event->getCause() === EntityDamageEvent::CAUSE_FALL) {
            $entity = $event->getEntity();
            if ($entity instanceof Player) {
                $playerData = AnniPlayerDataStorage::get($entity->getName());
                $job = $playerData->getCurrentJob();
                if ($job instanceof Acrobat) {
                    $event->setCancelled();
                }

                if ($job instanceof Assassin) {
                    if ($job->isOnLeap()) {
                        $event->setCancelled();
                    }
                }
            }
        }
    }
}