<?php


namespace copyanni\model;


use copyanni\form\SelectJobForm;
use copyanni\TypeList;
use DateTime;
use game_chef\api\GameChef;
use game_chef\TaskSchedulerStorage;
use pocketmine\block\BlockIds;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;

class JobChanger
{
    //name => handler
    /**
     * @var TaskHandler[]
     */
    static private array $handlers = [];

    static public function startLoad(Player $player): void {
        if (array_key_exists($player->getName(), self::$handlers)) return;
        if (GameChef::isRelatedWith($player, TypeList::Anni())) return;

        $name = $player->getName();
        $startTime = new DateTime();
        self::$handlers[$player->getName()] = TaskSchedulerStorage::get()->scheduleRepeatingTask(new ClosureTask(function (int $tick) use ($startTime, $name) {
            $player = Server::getInstance()->getPlayer($name);
            if ($player === null) {//Playerが抜けた
                self::$handlers[$name]->cancel();
                unset(self::$handlers[$name]);
                return;
            }

            $block = $player->getLevel()->getBlock($player->add(0, 1));
            if ($block->getId() !== BlockIds::PORTAL) {//Playerポータルに入っていない
                self::$handlers[$name]->cancel();
                unset(self::$handlers[$name]);
                return;
            }

            if ($startTime->diff(new DateTime())->s >= 5) {
                self::$handlers[$name]->cancel();
                unset(self::$handlers[$name]);

                //todo:安全なところにテレポート
                GameChef::setTeamGamePlayerSpawnPoint($player);
                $player->sendForm(new SelectJobForm());
                return;
            }
        }), 20 * 0.5);
    }
}