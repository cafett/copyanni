<?php


namespace copyanni\form;


use copyanni\model\Vote;
use copyanni\service\VoteService;
use copyanni\storage\VoteStorage;
use form_builder\models\simple_form_elements\SimpleFormButton;
use form_builder\models\SimpleForm;
use pocketmine\Player;

class VoteListForm extends SimpleForm
{
    public function __construct(bool $forManagement = false) {
        $buttons = [];
        if ($forManagement) {
            $buttons[] = new SimpleFormButton(
                "Voteを作成",
                null,
                function (Player $player) {
                    $vote = new Vote();
                    $vote->setUp();
                    VoteStorage::add($vote);
                }
            );
        }

        foreach (VoteStorage::getAll() as $vote) {
            $voteId = $vote->getId();
            $buttons[] = new SimpleFormButton(
                $vote->getDetail(),
                null,
                function (Player $player) use ($forManagement, $voteId) {
                    if ($forManagement) {
                        $player->sendForm(new VoteManagementForm($voteId));
                    } else {
                        VoteService::join($player, $voteId);
                    }
                }
            );
        }
        parent::__construct("Vote List", "", $buttons);
    }

    function onClickCloseButton(Player $player): void {
    }
}