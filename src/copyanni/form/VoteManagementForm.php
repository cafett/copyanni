<?php


namespace copyanni\form;


use copyanni\model\Vote;
use copyanni\model\VoteId;
use copyanni\storage\VoteStorage;
use form_builder\models\simple_form_elements\SimpleFormButton;
use form_builder\models\SimpleForm;
use game_chef\api\GameChef;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class VoteManagementForm extends SimpleForm
{

    public function __construct(VoteId $voteId) {
        parent::__construct(
            "Voteの管理",
            "",
            [
                new SimpleFormButton(
                    TextFormat::RED . "破棄",
                    null,
                    function (Player $player) use ($voteId) {
                        $vote = VoteStorage::find($voteId);
                        if ($vote === null) {
                            $player->sendMessage("voteが存在しませんでした");
                            return;
                        }

                        GameChef::finishGame($vote->getGameId());
                    }
                ),
            ]
        );
    }

    function onClickCloseButton(Player $player): void {
        // TODO: Implement onClickCloseButton() method.
    }
}