<?php


namespace copyanni\model;


class VoteStatus
{
    private string $value;

    private function __construct(string $value) {
        $this->value = $value;
    }

    static function MapElect(): self {
        return new self("MapElect");
    }

    static function TeamSelect(): self {
        return new self("TeamSelect");
    }

    static function DeclineNewPlayers(): self {
        return new self("Close");
    }

    public function equals(?self $status): bool {
        if ($status === null)
            return false;

        return $this->value === $status->value;
    }

    public function __toString() {
        return $this->value;
    }
}