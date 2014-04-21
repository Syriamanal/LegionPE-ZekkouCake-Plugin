<?php

namespace pemapmodder\legionpe\hub;

use pemapmodder\utils\CallbackEventExe;

use pocketmine\Server;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;

class Hub implements Listener{
	public $server;
	public function __construct(){
		$this->server = Server::getInstance();
		$pmgr = $this->server->getPluginManager();
		$pmgr->registerEvent("pocketmine\\event\\player\\PlayerInteractEvent", $this, EventPriority::HIGH, new CallbackEventExe(array($this, "onInteract")), $this);
	}
	public function onInteract(Event $data){
		
	}
	public static $hub;
	public static function init(){
		self::$hub=new Hub();
	}
}
