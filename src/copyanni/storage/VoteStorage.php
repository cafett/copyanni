<?php


namespace copyanni\storage;


use copyanni\model\Vote;
use copyanni\model\VoteId;
use game_chef\models\GameId;

class VoteStorage
{
    /**
     * @var Vote[]
     * (string)VoteId => Vote
     */
    private static array $voteList = [];

    static function add(Vote $vote): void {
        self::$voteList[strval($vote->getId())] = $vote;
    }

    static function getAll(): array {
        return self::$voteList;
    }

    static function find(?VoteId $voteId): ?Vote {
        if ($voteId === null) return null;

        if (array_key_exists(strval($voteId), self::$voteList)) {
            return self::$voteList[strval($voteId)];
        }
        return null;
    }

    static function getByGameId(GameId $gameId): Vote {
        foreach (self::$voteList as $vote) {
            if ($gameId->equals($vote->getGameId())) {
                return $vote;
            }
        }

        throw new \LogicException("そのGameId({$gameId})を持つVoteは存在しません");
    }

    static function delete(VoteId $voteId): void {
        self::$voteList[strval($voteId)]->close();
        unset(self::$voteList[strval($voteId)]);
    }
}