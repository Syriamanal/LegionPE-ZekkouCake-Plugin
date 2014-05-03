<?php

namespace pemapmodder\legionpe\mgs\ctf;

use pemapmodder\legionpe\hub\HubPlugin;
use pemapmodder\legionpe\mgs\MgMain;

use pemapmodder\utils\CallbackEventExe as EvtExe;
use pemapmodder\utils\FileUtils;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\level\Level;

class Main implements MgMain, Listener{
	public $status = 0;
	public $players = array(0=>array(), 1=>array(), 2=>array(), 3=>array());
	public function __construct(){
		$this->hub = HubPlugin::get();
		$this->server = Server::getInstance();
		$this->initialize();
	}
	protected function initialize(){
		// FileUtils::copy(RawLocs::basePath(), RawLocs::worldPath());
		// $this->current = new Game($this->server->getLevel(RawLocs::worldName()));
		// $this->server->registerEvent("pocketmine\\event\\server\\ServerStopEvent", $this, EventPriority::HIGH, new EvtExe(array($this, "finalize")), $this->hub);
	}
	public function onJoinMg(Player $p){
	}
	public function onQuitMg(Player $p){
	}
	public function getName(){
		return "CTF";
	}
	public function getSessionId(){
		return HubPlugin::CTF;
	}
	public function getSpawn(Player $p, $TID){
		// TODO
	}
	public function getDefaultChatChannel(Player $p, $TID){
		return "legionpe.chat.ctf.$TID";
	}
	public function isJoinable(){
		// TODO
		if(@$this->current instanceof Game){
			return $this->current->join($p);
		}
		return "Not started";
	}
	public function finalize(){
		@$this->current->finalize("server stop");
	}
	public static $ctf = false;
	public static function get(){
		return self::$ctf;
	}
	public static function init(){
		self::$ctf = new self();
	}
}