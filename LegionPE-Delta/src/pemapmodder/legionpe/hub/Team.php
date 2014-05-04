<?php

namespace pemapmodder\legionpe\hub;

use pemapmodder\legionpe\geog\RawLocs as RL;
use pemapmodder\utils\DummyPlugin;
use pemapmodder\utils\CallbackPluginTask;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\tile\Tile;

class Team{
	// static
	protected static $teams = array();
	public static function addPoints($i, $pts){
		self::get($i)->config["points"] += $pts;
	}
	protected static function evalI($i){
		if(is_int($i)) $i &= 0b11;
		elseif($i instanceof Player) $i = HubPlugin::get()->getDb($i)->get("team");
		else{
			trigger_error("Unexpected argument 1 (".print_r($i, true).") passed to ".get_class()."::evalI($i)", E_USER_ERROR);
			return;
		}
	}
	public static function get($i){
		return self::$teams[self::evalI($i)];
	}
	public static function init(){
		@mkdir(Server::getInstance()->getDatapath()."hub/teams/");
		for($i = 0; $i < 4; $i++)
			self::$teams[$i] = new self($i);
		Server::getInstance()->getScheduler()->scheduleRepeatingTask(new CallbackPluginTask(array(get_class(), "updateScoreBars"), HubPlugin::get()), 600);
	}
	public static function canJoin($team){
		$scores = array();
		foreach(self::$teams as $t){
			$scores[$t->getTeam()] = $t->config["members-cnt"];
		}
		$ts = self::$teams[$team]->config["members-cnt"];
		$max = max($scores);
		$percent = ($max - $ts) / $ts * 100;
		return $ts <= 5;
	}
	public static function updateSigns(){
		for($i = 0; $i < 4; $i++){
			if(self::canJoin($i)){
				DummyPlugin::getTile(RL::chooseTeamSigns($i))->setText("Tap me to join", "TEAM ".strtoupper(self::$teams[$i]->config["name"]));
			}
			else{
				DummyPlugin::getTile(RL::chooseTeamSigns($i))->setText("TEAM ".strtoupper(self::$teams[$i]->config["name"]), "is now full.", "Come back later", "or join others");
			}
		}
	}
	public static function updateScoreBars(){
		$scores = array();
		for($i = 0; $i < 4; $i++){
			$scores[$i] = self::$teams[$i]->config["points"];
		}
		$max = max($scores);
		for($i = 0; $i < 4; $i++){
			$percent = max(0, $scores[$i]) / $max * 100;
			RL::teamScoreBar($i, $percent)->setBlocks(Block::get(35, self::$teams[$i]->config["color-meta"]));
		}
		console("[INFO] Hub score bars have been updated.");
	}
	// non-static
	public $config = array();
	public function __construct($i){
		$this->team = $i;
		$path = \pocketmine\DATA."hub/teams/team-$i.yml";
		$this->path = $path;
		Server::getInstance()->getScheduler()->scheduleRepeatingTask(new CallbackPluginTask(array($this, "save"), HubPlugin::get()), 1200);
		if(is_file($path)){
			$this->config = yaml_parse(file_get_contents($path));
		}
		else{
			$names = array("magma", "lapiz", "lilac", "lime");
			$this->config["name"] = $names[$i];
			$metas = array(1, 3, 10, 5);
			$this->config["color-meta"] = $metas[$i];
			$this->config["points"] = 1000;
			$this->config["members-cnt"] = 10;
			file_put_contents($path, yaml_emit($this->config));
		}
	}
	public function save(){
		file_put_contents($this->path, yaml_emit($this->config));
	}
	public function join(Player $p){
		if(self::canJoin($this->team)){
			$this->config["members-cnt"]++;
			self::updateSigns();
			return "SUCCESS";
		}
		return "FULL";
	}
	public function getTeam(){
		return $this->team;
	}
	public function __toString(){
		return $this->config["name"];
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
		$this->save();
	}
}
