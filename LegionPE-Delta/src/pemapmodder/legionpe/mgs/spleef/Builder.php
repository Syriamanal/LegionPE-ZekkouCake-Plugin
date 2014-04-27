<?php

namespace pemapmodder\legionpe\mgs\spleef;

use pemapmodder\legionpe\geog\RawLocs;

use pemapmodder\utils\spaces\CylinderSpace as CS;

use pocketmine\block\Block;
use pocketmine\level\Position;

class Builder extends RawLocs{
	public static function build(Position $centre, $radius, Block $block, $floors, $height, $players, Block $pfloor, Block $wall, Block $ceil){
		for($i = 0; $i < $floors; $i++){
			$c = clone $centre;
			$cs = new CS(CS::Y, $c->subtract(0, $height * $i), $radius, 1);
			$cs->setBlocks($block);
		}
		$preps = array();
		for($j = 0; $j < $players; $j++){
			$deg = 360 / $players * $j;
			self::buildPrep(($preps[] = new Position($centre->x + cos(deg2rad($deg)), $centre->y + 4, $centre->z + sin(deg2rad($deg)), $centre->level)), $pfloor, $wall, $ceil, 2);
		}
		// TODO hollow circular wall; maybe just hand-make it?
	}
	public static function buildPrep(Position $pos, Block $floor, Block $wall, Block $ceil, $height){
		$c = clone $pos;
		self::sb($c->add(0, -1), $floor);
		for($i = 0; $i <= $height; $i++){
			self::sb($c->add(1, $i, 1), $wall);
			self::sb($c->add(1, $i, 0), $wall);
			self::sb($c->add(1, $i, -1), $wall);
			self::sb($c->add(0, $i, 1), $wall);
			self::sb($c->add(0, $i, -1), $wall);
			self::sb($c->add(-1, $i, 1), $wall);
			self::sb($c->add(-1, $i, 0), $wall);
			self::sb($c->add(-1, $i, -1), $wall);
		}
		self::sb($c->add(0, $height), $ceil);
	}
	protected static function sb(Position $pos, Block $block){
		$pos->level->setBlock($pos, $block, false, false, true);
	}
}
