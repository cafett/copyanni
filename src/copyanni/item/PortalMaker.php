<?php


namespace copyanni\item;


use copyanni\model\job\Transporter;
use pocketmine\item\ItemIds;

class PortalMaker extends SkillItem
{
    const NAME = "PortalMaker";
    const ID = ItemIds::FEATHER;
    const JOB_NAME = Transporter::NAME;
}