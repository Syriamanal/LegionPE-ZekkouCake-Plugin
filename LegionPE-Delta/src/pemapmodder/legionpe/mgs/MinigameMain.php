<?php

namespace pemapmodder\legionpe\mgs;

use pocketmine\Player;

interface MgMain{
	public function onJoin(Player $player);
	public function onQuit(Player $player);
}
