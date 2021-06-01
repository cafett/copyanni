<?php

namespace copyanni;

use copyanni\entity\FishingHook;
use copyanni\entity\ScorpioHookEntity;
use copyanni\form\VoteListForm;
use copyanni\listener\AnniGameListener;
use copyanni\model\VoteId;
use copyanni\scoreboard\AnniGameScoreboard;
use copyanni\scoreboard\VoteScoreboard;
use copyanni\service\VoteMapService;
use copyanni\storage\AnniPlayerDataStorage;
use copyanni\storage\PlayerDeviceDataStorage;
use copyanni\storage\VoteStorage;
use game_chef\api\GameChef;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class Main extends PluginBase implements Listener
{
    public function onEnable() {
        if (!$this->getConfig()->exists(VoteMapService::VoteWoldKey)) {
            $this->getConfig()->set(VoteMapService::VoteWoldKey, "");
            $this->getConfig()->save();
        }
        VoteMapService::init($this->getConfig()->get(VoteMapService::VoteWoldKey, ""));

        DataPath::init($this->getDataFolder());
        AnniGameScoreboard::init();
        VoteScoreboard::init();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getPluginManager()->registerEvents(new AnniGameListener($this->getScheduler()), $this);
        Entity::registerEntity(FishingHook::class, true, ["FishingHook", Entity::FISHING_HOOK]);
        Entity::registerEntity(ScorpioHookEntity::class, true, ["ScorpioHookEntity"]);
    }

    public function onJoin(PlayerJoinEvent $event) {
        $pk = new GameRulesChangedPacket();
        $pk->gameRules["doImmediateRespawn"] = [1, true];
        $event->getPlayer()->sendDataPacket($pk);

        $player = $event->getPlayer();
        AnniPlayerDataStorage::loadFromRepository($player->getName());
    }

    public function onQuit(PlayerQuitEvent $event) {
        $player = $event->getPlayer();
        $levelName = $player->getLevel()->getName();
        if (VoteMapService::isVoteLevel($levelName)) {
            $vote = VoteStorage::find(new VoteId(str_replace(VoteMapService::VoteWoldKey, "", $levelName)));
            if ($vote !== null) {
                $vote->quit($player);
            }
        }
    }

    public function onPacketReceived(DataPacketReceiveEvent $event) {
        $packet = $event->getPacket();
        if ($packet instanceof LoginPacket) {
            PlayerDeviceDataStorage::save($packet);
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (!($sender instanceof Player)) return false;
        if ($label === "vote") {
            if (count($args) === 1) {
                if ($args[0] === "manage") {
                    $sender->sendForm(new VoteListForm($sender->isOp()));
                    return true;
                }
            }

            $sender->sendForm(new VoteListForm());
            return true;
        } else if ($label === "hub") {
            $levelName = $sender->getLevel()->getName();
            if (VoteMapService::isVoteLevel($levelName)) {//voteからhub
                $vote = VoteStorage::find(new VoteId(str_replace(VoteMapService::VoteWoldKey, "", $levelName)));
                if ($vote !== null) {
                    $vote->quit($sender);
                }
                return true;
            }

            $playerData = GameChef::findPlayerData($sender->getName());
            //todo:anni以外の試合にも作用してしまう
            if ($playerData->getBelongGameId() !== null) {//試合に参加している
                GameChef::quitGame($sender);
            } else {//試合に参加していない
                $level = Server::getInstance()->getDefaultLevel();
                $sender->teleport($level->getSpawnLocation());
            }
        }
        return false;
    }
}