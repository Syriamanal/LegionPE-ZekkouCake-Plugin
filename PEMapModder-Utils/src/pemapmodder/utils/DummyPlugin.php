<?php

namespace pemapmodder\utils;

use pocketmine\level\Position;
use pocketmine\tile\Tile;
use pocketmine\utils\PluginBase;

if(!class_exists("pemapmodder\\utils\\DummyPlugin")){
	class DummyPlugin extends PluginBase{
		public static function getTile(Position $pos){
			foreach(Tile::getAll() as $t){
				if($t->x === $pos->x and $t->y === $pos->y and $t->z === $pos->z and $t->level->getName() === $pos->level->getName())
					return $t;
			}
			return null;
		}
		public function onLoad(){
			console("PEMapModder-Utils has been loaded!");
		}
	}
}
