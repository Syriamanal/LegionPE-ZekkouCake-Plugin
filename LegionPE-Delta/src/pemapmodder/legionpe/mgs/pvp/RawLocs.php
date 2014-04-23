<?php

namespace pemapmodder\legionpe\mgs\pvp;

use pemapmodder\legionpe\geog\RawLocs as ParentClass;

use pemapmodder\utils\spaces\CuboidSpace as CS;

use pocketmine\math\Vector3 as V3;

abstract class RawLocs extends ParentClass{
	public function safeArea(){
		return new CS(new V3(84, 0, 71), new V3(99, 127, 86), parent::pvp());
	}
}
