<?php

namespace pemapmodder\legionpe\mgs;

use pocketmine\Player;

interface MgMain{
	public function onJoinMg(Player $player);
	public function onQuitMg(Player $player);
	public function getName();
	public function getSessionId();
}
