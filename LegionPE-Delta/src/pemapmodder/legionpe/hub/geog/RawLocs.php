<?php

namespace pemapmodder\legionpe\geog;

use pocketmine\level\Level as Lv;

abstract class RawLocs{
	public static function chooseTeamStd(){
		return new Position(165.5, 47, 67.5, self::hub());
	}
	public static function chooseTeamSign($team){
		return new Position(166 - $team, 48, 60, self::hub());
	}
	public static function enterPvpPor(){
		return new MySpace(new Vector3(159, 30, 124), new Vector3(157, 42, 134), self::hub());
	}
	public static function enterPkPor(){
		// return new MySpace();
	}
	public static function hub(){
		return Lv::get("world");
	}
	public static function spleef(){
		return Lv::get("world_spleef");
	}
	public static function pk(){
		return self::parkour();
	}
	public static function parkour(){
		return Lv::get("world_parkour");
	}
	public static function pvp(){
		return Lv::get("world_pvp");
	}
}
