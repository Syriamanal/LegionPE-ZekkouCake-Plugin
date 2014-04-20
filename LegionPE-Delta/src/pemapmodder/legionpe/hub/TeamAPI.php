<?php

namespace pemapmodder\legionpe;

use pocketmine\Player;

class Team implements \ArrayAccess{
	// static
	public static $teams = array();
	public static function init(){
		for($i = 0; $i < 4; $i++)
			self::$teams[$i] = new self($i);
	}
	public static function canJoin($team){
		$scores = array();
		foreach(self::$teams as $t){
			$scores[$t->getTeam()] = $t["members-cnt"];
		}
		$ts = self::$teams[$team]["members-cnt"];
		$max = max($scores);
		$percent = ($max - $ts) / $ts * 100;
		return $ts <= 5;
	}
	public static function updateSigns(){
		
	}
	// non-static
	public $config = array();
	public function __construct($i){
		$this->team = $i;
		$path = \pocketmine\DATA."hub/teams/team-$i.yml";
		if(is_file($path)){
			$this->config = \yaml_parse(\file_get_contents($path));
		}
		else{
			$this->config["points"] = 1000;
			$this->config["members-cnt"] = 10;
			\file_put_contents($path, \yaml_emit($this->config));
		}
	}
	public function join(Player $p){
		if(self::canJoin($this->team)){
			$this->config["members-cnt"]++;
			self::updateSigns();
		}
	}
	public function getTeam(){
		return $this->team;
	}
	public function offsetExists($key){
		return array_key_exists($key, $this->config);
	}
	public function offsetUnset($key){
		unset($this->config[$key]);
	}
	public function offsetGet($key){
		return $this->config[$key];
	}
	public function offsetSet($key, $value){
		$this->config[$key] = $value;
	}
}
