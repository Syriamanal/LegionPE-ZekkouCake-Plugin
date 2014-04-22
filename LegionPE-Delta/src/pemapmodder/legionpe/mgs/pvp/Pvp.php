<?php

namespace pemapmodder\legionpe\mgs\pvp;

use pemapmodder\legionpe\hub\HubPlugin;
use pemapmodder\utils\CallbackEventExe as EvtExe;
use pemapmodder\utils\CallbackPluginTask as Task;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;

class Pvp implements Listener{
	public function __construct(){
		$this->server = Server::getInstance();
		$this->hub = HubPlugin::get();
		$this->server->getPluginManager()->registerEvent("pocketmine\\event\\entity\\EntityDeathEvent", $this, EventPriority::HIGH, new EvtExe(array($this, "onDeath")), $this->hub);
		$this->server->getPluginManager()->registerEvent("pocketmine\\event\\entity\\EntityHurtEvent", $this, EventPriority::HIGH, new EvtExe(array($this, "onHurt")), $this->hub);
	}
	public function onDeath(Event $event){
		$p = $event->getEntity();
		if(!($p instanceof Player)) return;
		$cause = $event->getCause();
		if($cause instanceof Player){
			$this->onKill($cause);
		}
	}
	public function onHurt(Event $event){
		$p = $event->getEntity();
		if(!($p instanceof Player)) return;
		$cause = $event->getCause();
		if(in_array($cause, array("suffocation", "falling")))
			$event->setCancelled();
	}
	public function onKill(Player $killer){
		
	}
	public static $inst = false;
	public static function init(){
		self::$inst = new self();
	}
}
