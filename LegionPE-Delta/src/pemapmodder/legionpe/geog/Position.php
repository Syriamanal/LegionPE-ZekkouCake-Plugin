<?php

namespace pemapmodder\legionpe\geog;

use pocketmine\level\Position as PmPos;
use pocketmine\level\Level;
use pocketmine\math\Vector3;

class Position extends PmPos{
	public function __construct($x, $y=0, $z=0, Level $level=null){
		if($x instanceof PmPos)
			parent::__construct($x->x, $x->y, $x->z, $z->level);
		else parent::__construct($x, $y, $z, $level);
	}
	public function equals(Vector3 $other){
		$result = $other->x === $this->x and $other->y === $this->y and $other->z === $this->z;
		if($other instanceof PmPos)
			$result = $result and $other->level->getName() === $this->level->getName();
		return $result;
	}
}
