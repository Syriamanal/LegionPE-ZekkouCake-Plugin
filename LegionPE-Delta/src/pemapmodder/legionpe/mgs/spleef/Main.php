<?php

namespace pemapmodder\legionpe\mgs\spleef;

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
	public function onMove(Event $evt){}
	/**
	 * @param PlayerInteractEvent $evt
	 * @priority HIGH
	*/
	public function onInteract(Event $evt){}
	public function join($arenaId, $player){}
	public function quit($player){
	}
	public static $instance = false;
	public static function get(){
		return self::$instance;
	}
	public static function init(){
		self::$instance = new self();
	}
}
