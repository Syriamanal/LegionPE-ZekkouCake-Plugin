<?php

namespace pemapmodder\legionpe\mgs\spleef;

use pocketmine\Server;
use pocketmine\event\Event;
use pocketmine\event\Listener;

class Main implements Listener{
	public $arenas = array();
	public $sessions = array();
	public function __construct(){
		// TODO initialize arenas with raw coords data
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
