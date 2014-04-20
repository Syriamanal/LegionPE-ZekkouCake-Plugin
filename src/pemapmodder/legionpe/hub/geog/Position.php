<?php

namespace pemapmodder\legionpe\geog;

use pocketmine\level\Position as PmPos;
use pocketmine\level\Level;

class Position extends PmPos{
	public function __construct($x, $y, $z, Level $level){
		parent::__construct($x, $y, $z, $level);
	}
}
