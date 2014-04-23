<?php

namespace pemapmodder\legionpe\mgs\pk;

use pemapmodder\legionpe\geog\RawLocs as ParentClass;

use pocketmine\math\Vector3;

abstract class RawLocs extends ParentClass{
	public static function fallY(){
		return parent::pk()->getSafeSpawn()->y - 1;
	}
	public abstract static function signPrefix(Vector3 $pos){
		foreach(array("x", "y", "z") as $c)
			eval("\$".$c." = \$pos->$c;");
		if($y === 74){
			if($z === 35 or $z === 99){
				if($x === 66)
					return "easy";
				if($x === 58)
					return "medium";
				if($x === 52)
					return "hard";
				if($x === 46)
					return "extreme";
			}
		}
		return false;
	}
}
