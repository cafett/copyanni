<?php

namespace copyanni\model;


use copyanni\model\job\Civilian;
use copyanni\model\job\Job;
use DateTime;
use pocketmine\Server;

class CoreGamePlayerData
{
    private string $name;
    private array $ownJobNames;
    private Job $currentJob;
    private ?DateTime $lastImmobilizedTime;

    public function __construct(string $name, array $ownJobNames, Job $job, ?DateTime $lastImmobilizedTime = null) {
        $this->name = $name;
        $this->ownJobNames = $ownJobNames;
        $this->currentJob = $job;
        $this->lastImmobilizedTime = $lastImmobilizedTime;
    }

    static function asNew(string $name): self {
        return new self($name, [Civilian::NAME], new Civilian());
    }

    public function setCurrentJob(Job $job): bool {
        if ($this->currentJob::NAME === $job::NAME) return false;
        if (!in_array($job::NAME, $this->ownJobNames)) return false;

        $this->currentJob = $job;
        return true;
    }

    public function addOwnJobName(string $name): bool {
        if (!in_array($name, $this->ownJobNames)) return false;

        $this->ownJobNames[] = $name;
        return true;
    }

    static function fromJson(array $json): self {
        return new self(
            $json["name"],
            $json["own_job_names"],
            Job::fromName($json["current_job_name"]),
        );
    }

    public function toJson(): array {
        return [
            "name" => $this->name,
            "own_job_names" => $this->ownJobNames,
            "current_job_name" => $this->currentJob::NAME,
        ];
    }

    public function getName(): string {
        return $this->name;
    }

    public function getOwnJobNames(): array {
        return $this->ownJobNames;
    }

    public function getCurrentJob(): Job {
        return $this->currentJob;
    }

    public function updateCurrentJob(Job $job): void {
        $player = Server::getInstance()->getPlayer($this->name);
        $this->currentJob->onChangeJob($player);
        $this->currentJob = $job;
    }

    public function getLastImmobilizedTime(): ?DateTime {
        return $this->lastImmobilizedTime;
    }

    public function setLastImmobilizedTime(?DateTime $lastImmobilizedTime): void {
        $this->lastImmobilizedTime = $lastImmobilizedTime;
    }
}