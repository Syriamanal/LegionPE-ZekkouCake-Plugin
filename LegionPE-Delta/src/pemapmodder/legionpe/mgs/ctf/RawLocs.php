<?php

namespace pemapmodder\legionpe\mgs\ctf;

use pemapmodder\legionpe\geog\RawLocs as ParentClass;

use pocketmine\Server;

abstract class Rawlocs extends ParentClass{
	public final static function newSpawn($team){
		
	}
	public final static function baseName(){
		return "world_base_ctf";
	}
	public final static function basePath(){
		return Server::getInstance()->getDatapath()."worlds/".self::baseName();
	}
	public final static function worldName(){
		return "world_temp_ctf";
	}
	public final static function worldPath(){
		return Server::getInstance()->getDatapath()."worlds/".self::worldName();
	}
}
