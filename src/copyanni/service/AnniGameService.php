<?php

namespace copyanni\service;

use copyanni\block\Nexus;
use copyanni\model\job\Handyman;
use copyanni\scoreboard\AnniGameScoreboard;
use copyanni\GameTypeList;
use copyanni\storage\AnniPlayerDataStorage;
use game_chef\api\GameChef;
use game_chef\api\TeamGameBuilder;
use game_chef\models\GameId;
use game_chef\models\Score;
use game_chef\models\Team;
use game_chef\models\TeamGame;
use game_chef\pmmp\bossbar\Bossbar;
use pocketmine\entity\Attribute;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class AnniGameService
{
    public static function buildGame(string $mapName): ?GameId {
        $builder = new TeamGameBuilder();
        try {
            $builder->setNumberOfTeams(4);
            $builder->setGameType(GameTypeList::anni());
            $builder->setTimeLimit(null);
            $builder->setVictoryScore(null);
            $builder->setCanJumpIn(true);
            $builder->selectMapByName($mapName);

            $builder->setFriendlyFire(false);
            $builder->setMaxPlayersDifference(2);
            $builder->setCanMoveTeam(true);

            $game = $builder->build();
            GameChef::registerGame($game);
            return $game->getId();
        } catch (\Exception $e) {
            Server::getInstance()->getLogger()->error($e->getMessage());
            return null;
        }
    }

    public static function sendToGame(Player $player, TeamGame $game): void {

        $levelName = $game->getMap()->getLevelName();
        $level = Server::getInstance()->getLevelByName($levelName);

        $player->teleport($level->getSpawnLocation());
        $player->teleport(Position::fromObject($player->getSpawn(), $level));

        //ボスバー
        $bossbar = new Bossbar($player, GameTypeList::anni()->toBossbarType(), "", 1.0);
        $bossbar->send();

        //スコアボード
        AnniGameScoreboard::send($player, $game);

        self::initPlayerStatus($player);

        $playerData = GameChef::findPlayerData($player->getName());
        $team = $game->getTeamById($playerData->getBelongTeamId());
        $player->sendMessage("あなたは" . $team->getTeamColorFormat() . $team->getName() . TextFormat::RESET . "です");

        //ネームタグ
        $player->setNameTag("[{$team->getName()}] " . $player->getName());
    }

    public static function backToLobby(Player $player): void {
        self::resetPlayerStatus($player);
        $level = Server::getInstance()->getDefaultLevel();
        $player->teleport($level->getSpawnLocation());
    }

    public static function resetPlayerStatus(Player $player): void {
        $player->getAttributeMap()->getAttribute(Attribute::MOVEMENT_SPEED)->setValue(0.1);
        $player->removeAllEffects();

        //ボスバー削除
        foreach (Bossbar::getBossbars($player) as $bossbar) {
            $bossbar->remove();
        }

        //スコアボード削除
        AnniGameScoreboard::delete($player);

        //ネームタグリセット
        $player->setNameTag($player->getName());
    }

    public static function initPlayerStatus(Player $player): void {
        $playerData = AnniPlayerDataStorage::get($player->getName());
        $job = $playerData->getCurrentJob();

        //エフェクト
        foreach ($job->getEffects() as $effect) {
            $player->addEffect($effect);
        }

        //インベントリ
        $player->getInventory()->setContents($job->getInitialInventory());
    }

    static function breakNexus(TeamGame $teamGame, Team $targetTeam, Player $attacker, Vector3 $nexusPosition): void {
        $attackerData = GameChef::findPlayerData($attacker->getName());
        $attackerTeam = $teamGame->getTeamById($attackerData->getBelongTeamId());

        //メッセージと音
        $playerDataList = GameChef::getPlayerDataList($teamGame->getId());
        foreach ($playerDataList as $playerData) {
            $player = Server::getInstance()->getPlayer($playerData->getName());

            if ($player->distance($nexusPosition) <= 10) {
                //音:半径20m以下
                SoundService::play($player, $nexusPosition, "random.anvil_land", 50, 1);

            } else if ($playerData->getBelongTeamId()->equals($targetTeam->getId())) {
                //20m以上で破壊されてるコアのチームなら
                SoundService::play($player, $player->getPosition(), "note.pling", 50, 2);
            }

            //TIP
            if ($playerData->getBelongTeamId()->equals($targetTeam->getId())) {
                $player->sendTip($attackerTeam->getTeamColorFormat() . $attacker->getName() . TextFormat::RESET . "にコアを攻撃されています\nHP:" . (Nexus::MAX_HEALTH - $targetTeam->getScore()->getValue()));
            } else {
                $player->sendTip(
                    $attackerTeam->getTeamColorFormat() . $attacker->getName() . TextFormat::RESET . "が" . $targetTeam->getTeamColorFormat() . $targetTeam->getName() . TextFormat::RESET . "を攻撃中"
                );
            }
        }

        if (AnniPlayerDataStorage::get($attacker->getName())->getCurrentJob() instanceof Handyman) {
            if ($attackerTeam->getScore() < Nexus::MAX_HEALTH) {//チームがすでに負けていたら無し
                $phase = self::getGamePhase($teamGame->getId());
                $percent = 0;
                switch ($phase) {
                    case 1:
                        $percent = 0;
                        break;
                    case 2:
                        $percent = 0.20;
                        break;
                    case 3:
                        $percent = 0.15;
                        break;
                    case 4:
                        $percent = 0.10;
                        break;
                    case 5:
                        $percent = 0.07;
                        break;
                }
                if (mt_rand(1, 100) <= $percent * 100) {
                    GameChef::addTeamGameScore($teamGame->getId(), $attackerTeam->getId(), new Score(-1));
                }
            }
        }

        GameChef::addTeamGameScore($teamGame->getId(), $targetTeam->getId(), new Score(1));
    }

    public static function getGamePhase(GameId $gameId): ?int {
        $timer = GameChef::findGameTimer($gameId);
        if ($timer === null) return null;

        $phase = floor($timer->getElapsedTime() / 60 * 10);
        if ($phase < 1) return 1;
        if ($phase > 5) return 5;
        return $phase;
    }

    public static function generateDetailText(TeamGame $game): string {
        $text = "map:{$game->getMap()->getName()},";
        $text .= "phase:" . AnniGameService::getGamePhase($game->getId()) . "\n";
        foreach ($game->getTeams() as $team) {
            $text .= $team->getTeamColorFormat() . $team->getName() . TextFormat::RESET . ":" . (Nexus::MAX_HEALTH - $team->getScore()->getValue()) . ",";
        }
        return $text;
    }
}