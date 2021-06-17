<?php


namespace copyanni\form;

use copyanni\model\job\Job;
use copyanni\storage\AnniPlayerDataStorage;
use form_builder\models\simple_form_elements\SimpleFormButton;
use form_builder\models\SimpleForm;
use muqsit\invmenu\InvMenu;
use pocketmine\Player;

class SelectJobForm extends SimpleForm
{
    public function __construct() {
        $buttons = [];
        foreach (Job::JOB_CLASS_NAMES as $jobName) {
            $buttons[] = new SimpleFormButton(
                $jobName,
                null,
                function (Player $player) use ($jobName) {
                    $job = Job::fromName($jobName);
                    AnniPlayerDataStorage::get($player->getName())->setCurrentJob($job);

                    $player->teleport($player->getSpawn());

                    $menu = InvMenu::create(InvMenu::TYPE_CHEST);
                    $menu->getInventory()->setContents($job->getInitialInventory());
                    $menu->setName($job::NAME);
                    $menu->send($player);
                }
            );
        }
        parent::__construct("職業を変更", "", $buttons);
    }

    function onClickCloseButton(Player $player): void {
        $player->teleport($player->getSpawn());
    }
}