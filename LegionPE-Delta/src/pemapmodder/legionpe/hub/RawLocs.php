<?php

namespace pemapmodder\legionpe\geog;

use pemapmodder\utils\CuboidSpace as Space;

use pocketmine\level\Level as Lv;
use pocketmine\math\Vector3;

abstract class RawLocs{
	public static function chooseTeamStd(){
		return new Position(165.5, 47, 67.5, self::hub());
	}
	public static function chooseTeamSign($team){
		return new Position(166 - $team, 48, 60, self::hub());
	}
	public static function enterPvpPor(){
		return new Space(new Vector3(159, 30, 124), new Vector3(157, 42, 134), self::hub());
	}
	public static function enterPkPor(){
		return new Space(new Vector3(134, 30, 159), new Vector3(124, 42, 157), self::hub());
	}
	public static function hub(){
		return Lv::get("world");
	}
	public static function spleef(){
		return Lv::get("world_spleef");
	}
	public static function parkour(){
		return Lv::get("world_parkour");
	}
	public static function pvp(){
		return Lv::get("world_pvp");
	}
}
