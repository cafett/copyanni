<?php


namespace copyanni\form;


use copyanni\model\VoteId;
use copyanni\storage\VoteStorage;
use form_builder\models\simple_form_elements\SimpleFormButton;
use form_builder\models\SimpleForm;
use pocketmine\Player;

class VoteMapForm extends SimpleForm
{
    public function __construct(VoteId $voteId, array $mapNames) {
        $buttons = [];
        foreach ($mapNames as $mapName) {
            $buttons[] = new SimpleFormButton(
                $mapName,
                null,
                function (Player $player) use ($voteId, $mapName) {
                    $vote = VoteStorage::find($voteId);
                    if ($vote === null) return;

                    $vote->voteMap($player, $mapName);
                }
            );
        }
        parent::__construct(
            "マップ投票",
            "",
            $buttons
        );
    }


    function onClickCloseButton(Player $player): void {}
}