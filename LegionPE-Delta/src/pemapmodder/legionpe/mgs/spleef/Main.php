<?php

namespace pemapmodder\legionpe\mgs\spleef;

use pemapmodder\utils\CallbackEventExe;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\block\Block;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;

class Main implements Listener{
	public $arenas = array();
	public $sessions = array();
	public function __construct(){
		// TODO initialize arenas with raw coords data
		// E.g.:
		/*
		for($id = 0; $id < $max; $id++){
			$centre = $this->getCentreById();
			$this->arenas[$id] = new Arena($id, $centre, 10, 4, 8, Block::get(80), Block::get(7), Block::get(20), Block::get(20));
		}
		*/
		$this->server = Server::getInstance();
		$pm = $this->server->getPluginManager();
		foreach(array(
				array("entity\\EntityMoveEvent", "onMove"),
				array("player\\PlayerInteractEvent", "onInteract"),
				array("player\\PlayerJoinEvent", "onJoin")
				array("player\\PlayerQuitEvent", "onQuit")) as $ev)
			$pm->registerEvent("pocketmine\\event\\".$ev[0], $this, EventPriority::HIGH, new CallbackEventExe(array($this, $ev[1])), HubPlugin::get());
	}
	public function onMove(Event $evt){
		if($evt->getEntity() instanceof Player){
			if(($sid = $this->sessions[$evt->getEntity()->CID]) !== -1)
				$this->arenas[$sid]->onMove($evt);
		}
	}
	public function onInteract(Event $evt){
		if(($sid = $this->sessions[$evt->getPlayer()->CID]) !== -1)
			$this->arenas[$sid]->onInteract($evt);
	}
	public function onQuit(Event $event){
		$p = $event->getPlayer();
		if(!isset($this->sessions[$p->CID])) return;
		if(($s = $this->sessions[$p->CID]) !== -1){
			$this->arenas[$sid]->quit($event->getPlayer(), "logout");
		}
		unset($this->sessions[$p->CID]);
	}
	public function onJoin(Event $event){
		$this->sessions[$event->getPlayer()->CID] = -1;
	}
	public function join($sid, Player $player){
		$this->sessions[$player->CID] = $sid;
	}
	public function quit(Player $player){
		$this->sessions[$player->CID] = -1;
	}
	public static $instance = false;
	public static function get(){
		return self::$instance;
	}
	public static function init(){
		self::$instance = new self();
	}
}
