<?php


namespace cafett\entity;


use pocketmine\entity\projectile\Projectile;

class FishingHook extends Projectile
{
    public const NETWORK_ID = self::FISHING_HOOK;

    public $width = 0.25;
    public $height = 0.25;
    protected $gravity = 0.04;
    protected $drag = 0.04;
}