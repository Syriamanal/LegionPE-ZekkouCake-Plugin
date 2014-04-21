<?php

namespace pemapmodder\legionpe\hub;

use pemapmodder\legionpe\geog\RawLocs as RL;
use pemapmodder\utils\CallbackEventExe;
use pemapmodder\utils\CallbackPluginTask;

use pocketmine\Server;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;

class Hub implements Listener{
	public $server;
	public $teleports = array();
	public function __construct(){
		$this->server = Server::getInstance();
		$pmgr = $this->server->getPluginManager();
		$pmgr->registerEvent("pocketmine\\event\\player\\PlayerInteractEvent", $this, EventPriority::HIGH, new CallbackEventExe(array($this, "onInteract")), HubPlugin::get());
		$pmgr->registerEvent("pocketmine\\event\\entity\\EntityMoveEvent", $this, EventPriority::HIGH, new CallbackEventExe(array($this, "onMove")), HubPlugin::get());
	}
	public function onInteract(Event $evt){
		$tar = $evt->getBlock();
		$p = $evt->getPlayer();
	}
	public function onMove(Event $evt){
		$p = $evt->getPlayer();
		if(time() - @$this->teleports[strtolower($p->getName())] <= 3)
			return;
		if(RL::enterPvpPor()->isInside($p)){
			$this->server->getScheduler()->scheduleDelayedTask(new CallbackPluginTask(array($p, "teleport"), $this, RL::pvpSpawn()), 40);
			$p->sendMessage("You are going to be teleported to the");
			$p->sendMessage("  PvP world in 2 seconds! You might lag!");
			$this->teleports[strtolower($p->getName())] = time();
		}
		elseif(RL::enterPkPor()->isInside($p)){
			$this->server->getScheduler()->scheduleDelayedTask(new CallbackPlpuginTask(array($p, "teleport"), $this, RL::pkSpawn()), 40);
			$p->sendMessage("You are going to be teleported to the");
			$p->sendMessage("  parkour world in 2 seconds! You might lag!");
			$this->teleports[strtolower($p->getName())] = time();
		}
	}
	public static $hub = false;
	public static function init(){
		self::$hub=new self();
	}
}
