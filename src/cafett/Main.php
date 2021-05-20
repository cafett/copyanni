<?php

namespace cafett;

use cafett\entity\FishingHook;
use cafett\listener\CoreGameListener;
use cafett\scoreboard\CoreGameScoreboard;
use cafett\storage\CoreGamePlayerDataStorage;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener
{
    public function onEnable() {
        DataPath::init($this->getDataFolder());
        CoreGameScoreboard::init();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getPluginManager()->registerEvents(new CoreGameListener($this->getScheduler()), $this);
        Entity::registerEntity(FishingHook::class, true, ["FishingHook", Entity::FISHING_HOOK]);
    }

    public function onJoin(PlayerJoinEvent $event) {
        $pk = new GameRulesChangedPacket();
        $pk->gameRules["doImmediateRespawn"] = [1, true];
        $event->getPlayer()->sendDataPacket($pk);

        $player = $event->getPlayer();
        CoreGamePlayerDataStorage::loadFromRepository($player->getName());
    }
}