<?php

namespace copyanni;

use copyanni\entity\FishingHook;
use copyanni\entity\ScorpioHookEntity;
use copyanni\listener\AnniGameListener;
use copyanni\scoreboard\AnniGameScoreboard;
use copyanni\storage\AnniPlayerDataStorage;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener
{
    public function onEnable() {
        DataPath::init($this->getDataFolder());
        AnniGameScoreboard::init();
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
}