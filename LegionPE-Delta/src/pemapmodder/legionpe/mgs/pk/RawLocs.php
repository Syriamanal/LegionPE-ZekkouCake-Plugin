<?php

namespace pemapmodder\legionpe\mgs\pk;

use pemapmodder\legionpe\geog\RawLocs as ParentClass;

use pocketmine\math\Vector3;

abstract class RawLocs extends ParentClass{
	public static function fallY(){
		return parent::pk()->getSafeSpawn()->y - 1;
	}
	public abstract static function signPrefix(Vector3 $pos); // TODO!!!
}
