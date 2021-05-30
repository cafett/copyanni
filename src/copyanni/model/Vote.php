<?php


namespace copyanni\model;


use copyanni\GameTypeList;
use copyanni\item\hotbar_menu\VoteHotbarMenu;
use copyanni\service\AnniGameService;
use copyanni\service\VoteMapService;
use game_chef\api\GameChef;
use game_chef\models\GameId;
use game_chef\TaskSchedulerStorage;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;

//todo:万能クラスになってる
class Vote
{
    private VoteId $id;
    private ?GameId $gameId;
    private VoteStatus $status;

    private array $mapOptions;
    //playerName=>mapName
    private array $mapVotes = [];

    private TaskHandler  $handler;

    public function __construct() {
        $this->id = VoteId::asNew();
        $this->status = VoteStatus::MapElect();
        $this->mapOptions = GameChef::getTeamGameMapNames(GameTypeList::anni());
    }

    public function close(): void {
        $this->handler->cancel();
        $level = VoteMapService::getVoteLevel($this->id);
        foreach ($level->getPlayers() as $player) {
            $level = Server::getInstance()->getDefaultLevel();
            $player->teleport($level->getSpawnLocation());
        }

        GameChef::deleteWorld($level->getName());
    }

    public function join(Player $player): void {
        //todo:前回参加していた試合のvoteなら、okにする
        if ($this->status->equals(VoteStatus::DeclineNewPlayers())) {
            $player->sendMessage("voteに参加できませんでした");
            return;
        }

        if ($this->status->equals(VoteStatus::MapElect())) {
            $this->mapVotes[$player->getName()] = "";

            $level = VoteMapService::getVoteLevel($this->id);
            $player->teleport($level->getSpawnLocation());

            $menu = new VoteHotbarMenu($player, $this);
            $menu->send();

            //16人以上なら1分後にマップ選択を締め切り、チーム選択を開始する
            if (count($this->mapVotes) >= 16) {
                $this->handler = TaskSchedulerStorage::get()->scheduleDelayedTask(new ClosureTask(function (int $tick): void {
                    self::startTeamSelecting();
                }), 20 * 60);
            }
        }
    }

    public function quit(Player $player): void {
        if ($this->status->equals(VoteStatus::MapElect())) {
            unset($this->mapVotes[$player->getName()]);
        }

        $level = Server::getInstance()->getDefaultLevel();
        $player->teleport($level->getSpawnLocation());
    }

    public function startTeamSelecting(): bool {
        if ($this->status->equals(VoteStatus::MapElect())) {
            $selectedMapName = self::getMostPopularMapName();
            $this->gameId = AnniGameService::buildGame($selectedMapName);//試合を作成

            foreach (VoteMapService::getVoteLevel($this->id)->getPlayers() as $player) {
                $player->sendMessage("マップは" . $selectedMapName . "に決まりました");

                $menu = new VoteHotbarMenu($player, $this);
                $menu->send();
            }
            $this->status = VoteStatus::TeamSelect();

            //3分後に試合開始
            $this->handler = TaskSchedulerStorage::get()->scheduleDelayedTask(new ClosureTask(function (int $tick): void {
                GameChef::startGame($this->gameId);
            }), 20 * 60 * 3);
            return true;
        }
        return false;
    }

    public function declineNewPlayers(): bool {
        if ($this->status->equals(VoteStatus::MapElect())) {
            $this->status = VoteStatus::DeclineNewPlayers();
            return true;
        }
        return false;
    }

    public function voteMap(string $playerName, string $mapName): bool {
        if ($this->status->equals(VoteStatus::MapElect())) {
            $this->mapVotes[$playerName] = $mapName;
            return true;
        }
        return false;
    }

    public function getMostPopularMapName(): string {
        $mapScores = [];
        foreach ($this->mapOptions as $mapName) {
            $mapScores[$mapName] = 0;
        }

        foreach ($this->mapVotes as $playerName => $mapName) {
            $mapScores[$mapName] = $mapScores[$mapName] + 1;
        }

        asort($mapScores);
        return array_key_first($mapScores);
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