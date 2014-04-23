<?php

namespace pemapmodder\legionpe\mgs\spleef;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\block\Block;
use pocketmine\event\Event;
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
		$this->server->getPluginManager()->registerEvents($this, $this);
	}
	/**
	 * @param EntityMoveEvent $evt
	 * @priority HIGH
	 */
	public function onMove(Event $evt){
		if($evt->getEntity() instanceof Player){
			if(($sid = $this->sessions[$evt->getEntity()->getCID()]) !== -1)
				$this->arenas[$sid]->onMove($evt);
		}
	}
	/**
	 * @param PlayerInteractEvent $evt
	 * @priority HIGH
	*/
	public function onInteract(Event $evt){
		if(($sid = $this->sessions[$evt->getPlayer()->getCID()]) !== -1)
			$this->arenas[$sid]->onInteract($evt);
	}
	/**
	 * @param PlayerQuitEvent $event
	 * @priority HIGH
	 */
	public function onQuit(Event $event){
		$p = $event->getPlayer();
		if(!isset($this->sessions[$p->getCID()])) return;
		if(($s = $this->sessions[$p->getCID()]) !== -1){
			$this->arenas[$sid]->quit($event->getPlayer(), "logout");
		}
		unset($this->sessions[$p->getCID()]);
	}
	/**
	 * @param PlayerjoinEvent $event
	 * @priority HIGH
	 */
	public function onJoin(Event $event){
		$this->sessions[$event->getPlayer()->getCID()] = -1;
	}
	public function join($sid, Player $player){
		$this->sessions[$player->getCID()] = $sid;
	}
	public function quit(Player $player){
		$this->sessions[$player->getCID()] = -1;
	}
	public static $instance = false;
	public static function get(){
		return self::$instance;
	}
	public static function init(){
		self::$instance = new self();
	}
}
