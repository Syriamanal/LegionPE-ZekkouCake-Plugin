<?php

namespace pemapmodder\legionpe\mgs\ctf;

use pemapmodder\legionpe\hub\HubPlugin;

use pemapmodder\utils\FileUtils;

use pocketmine\Player;
use pocketmine\Server;

class Main implements MgMain{
	public $status = 0;
	public $players = array(0=>array(), 1=>array(), 2=>array(), 3=>array());
	public function __construct(){
		$this->hub = HubPlugin::get();
		$this->server = Server::getInstance();
		$this->initialize();
	}
	protected function initialize(){
		FileUtils::copy(RawLocs::basePath(), RawLocs::worldPath());
	}
	public function onJoinMg(Player $p){
	}
	public function onQuitMg(Player $p){
	}
	public function getName(){
		return "CTF";
	}
	public function getSpawn(Player $p, $TID){
		// TODO
	}
	public function getDefaultChatChannel(Player $p, $TID){
		return "legionpe.chat.ctf.$TID";
	}
	public function isJoinable(){
		// TODO
	}
	public static $ctf = false;
	public static function get(){
		return self::$ctf;
	}
	public static function init(){
		self::$ctf = new self();
	}
}
