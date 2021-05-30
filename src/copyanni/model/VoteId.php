<?php


namespace copyanni\model;



class VoteId
{
    private string $value;

    public function __construct(string $value) {
        $this->value = $value;
    }

    static function asNew(): self {
        return new self(uniqid());
    }

    public function __toString() {
        return $this->value;
    }

    public function equals(?self $id): bool {
        if ($id === null)
            return false;

        return $this->value === $id->value;
    }
}