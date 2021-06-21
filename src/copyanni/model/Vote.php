<?php


namespace copyanni\model;


use copyanni\scoreboard\VoteScoreboard;
use copyanni\TypeList;
use copyanni\item\hotbar_menu\VoteHotbarMenu;
use copyanni\service\AnniGameService;
use copyanni\service\VoteMapService;
use game_chef\api\GameChef;
use game_chef\models\GameId;
use game_chef\pmmp\bossbar\Bossbar;
use game_chef\TaskSchedulerStorage;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;

//todo:万能クラスになってる
class Vote
{
    private VoteId $id;
    private ?GameId $gameId = null;
    private VoteStatus $status;

    private array $mapOptions;
    //playerName=>mapName
    private array $mapVotes = [];

    private ?TaskHandler $bossbarTaskHandler = null;
    private int $elapsedTime = 0;
    private int $mapElectTime = 120;
    private int $teamSelectTime = 120;

    private ?TaskHandler  $handler = null;

    public function __construct() {
        $this->id = VoteId::asNew();
        $this->status = VoteStatus::MapElect();
        $this->mapOptions = GameChef::getTeamGameMapNamesByType(TypeList::Anni());
    }

    public function close(): void {
        if ($this->handler !== null) $this->handler->cancel();
        if ($this->bossbarTaskHandler !== null) $this->bossbarTaskHandler->cancel();

        $level = VoteMapService::getVoteLevel($this->id);
        foreach ($level->getPlayers() as $player) {
            $level = Server::getInstance()->getDefaultLevel();
            $player->teleport($level->getSpawnLocation());
        }

        GameChef::deleteWorld($level->getName());
    }

    public function setUp(): void {
        VoteMapService::generateVoteLevel($this->id);
    }

    public function join(Player $player): void {
        //todo:前回参加していた試合のvoteなら、okにする
        if ($this->status->equals(VoteStatus::DeclineNewPlayers())) {
            $player->sendMessage("voteに参加できませんでした");
            return;
        }

        VoteScoreboard::send($player, $this);
        if ($this->status->equals(VoteStatus::MapElect())) {
            $this->mapVotes[$player->getName()] = "";

            $level = VoteMapService::getVoteLevel($this->id);
            $player->teleport($level->getSpawnLocation());

            $menu = new VoteHotbarMenu($player, $this);
            $menu->send();

            //16人以上なら1分後にマップ選択を締め切り、チーム選択を開始する
            if ($this->status->equals(VoteStatus::MapElect()) and count($this->mapVotes) >= 1) {
                $this->bossbarTaskHandler = TaskSchedulerStorage::get()->scheduleRepeatingTask(new ClosureTask(function (int $tick): void {
                    $this->elapsedTime++;
                    $title = "マップ選択";
                    $percentage = ($this->mapElectTime - $this->elapsedTime) / $this->mapElectTime;

                    foreach (VoteMapService::getVoteLevel($this->id)->getPlayers() as $player) {
                        $bossbar = Bossbar::findByType($player, TypeList::VoteBossbar());
                        if ($bossbar === null) {
                            $bossbar = new Bossbar($player, TypeList::VoteBossbar(), $title, $percentage);
                            $bossbar->send();
                        } else {
                            $bossbar->updatePercentage($percentage);
                        }
                    }
                }), 20);

                $this->handler = TaskSchedulerStorage::get()->scheduleDelayedTask(new ClosureTask(function (int $tick): void {
                    self::startTeamSelecting();
                }), 20 * $this->mapElectTime);
            }
        }
    }

    public function quit(Player $player): void {
        if ($this->status->equals(VoteStatus::MapElect())) {
            unset($this->mapVotes[$player->getName()]);
        }
        VoteScoreboard::update(VoteMapService::getVoteLevel($this->id)->getPlayers(), $this);
        $bossbar = Bossbar::findByType($player, TypeList::VoteBossbar());
        if ($bossbar !== null) $bossbar->remove();

        $level = Server::getInstance()->getDefaultLevel();
        $player->teleport($level->getSpawnLocation());
    }

    public function startTeamSelecting(): void {
        $this->bossbarTaskHandler->cancel();
        $this->elapsedTime = 0;
        $this->bossbarTaskHandler = TaskSchedulerStorage::get()->scheduleRepeatingTask(new ClosureTask(function (int $tick): void {
            $this->elapsedTime++;

            $title = "チーム選択";
            $percentage = ($this->teamSelectTime - $this->elapsedTime) / $this->teamSelectTime;

            foreach (VoteMapService::getVoteLevel($this->id)->getPlayers() as $player) {
                $bossbar = Bossbar::findByType($player, TypeList::VoteBossbar());
                if ($bossbar === null) {
                    $bossbar = new Bossbar($player, TypeList::VoteBossbar(), $title, $percentage);
                    $bossbar->send();
                } else {
                    $bossbar->updatePercentage($percentage);
                }
            }
        }), 20);


        if ($this->status->equals(VoteStatus::MapElect())) {
            $selectedMapName = array_key_first(self::getMapElectResult());
            $this->gameId = AnniGameService::buildGame($selectedMapName);//試合を作成

            $this->status = VoteStatus::TeamSelect();
            VoteScoreboard::update(VoteMapService::getVoteLevel($this->id)->getPlayers(), $this);
            foreach (VoteMapService::getVoteLevel($this->id)->getPlayers() as $player) {
                $player->sendMessage("マップは" . $selectedMapName . "に決まりました");

                $menu = new VoteHotbarMenu($player, $this);
                $menu->send();
            }

            //3分後に試合開始
            $this->handler = TaskSchedulerStorage::get()->scheduleDelayedTask(new ClosureTask(function (int $tick): void {
                $this->bossbarTaskHandler->cancel();
                foreach (VoteMapService::getVoteLevel($this->id)->getPlayers() as $player) {
                    $bossbar = Bossbar::findByType($player, TypeList::VoteBossbar());
                    if ($bossbar !== null) $bossbar->remove();
                }
                GameChef::startGame($this->gameId);
            }), 20 * $this->teamSelectTime);
        }
    }

    public function declineNewPlayers(): bool {
        if ($this->status->equals(VoteStatus::MapElect())) {
            $this->status = VoteStatus::DeclineNewPlayers();
            return true;
        }
        return false;
    }

    public function voteMap(Player $player, string $mapName): void {
        if ($this->status->equals(VoteStatus::MapElect())) {
            $this->mapVotes[$player->getName()] = $mapName;
            $player->sendMessage($mapName . "に投票しました");
            VoteScoreboard::update(VoteMapService::getVoteLevel($this->id)->getPlayers(), $this);
            return;
        }
        $player->sendMessage($mapName . "投票できませんでした");
    }

    public function getMapElectResult(): array {
        $mapScores = [];
        foreach ($this->mapOptions as $mapName) {
            $mapScores[$mapName] = 0;
        }

        foreach ($this->mapVotes as $playerName => $mapName) {
            $mapScores[$mapName] = $mapScores[$mapName] + 1;
        }
        asort($mapScores);

        return $mapScores;
    }

    public function getId(): VoteId {
        return $this->id;
    }

    public function getGameId(): ?GameId {
        return $this->gameId;
    }

    public function getDetail(): string {
        if ($this->gameId !== null) {
            $game = GameChef::findGameById($this->gameId);
            return AnniGameService::generateDetailText($game);
        } else {
            return "人数:" . count($this->mapVotes);//todo:改善する
        }
    }

    public function getStatus(): VoteStatus {
        return $this->status;
    }

    public function getMapOptions(): array {
        return $this->mapOptions;
    }
}