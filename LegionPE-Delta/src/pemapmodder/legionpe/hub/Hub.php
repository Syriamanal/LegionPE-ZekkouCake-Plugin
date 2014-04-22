<?php

namespace pemapmodder\legionpe\hub;

use pemapmodder\legionpe\geog\RawLocs as RL;
use pemapmodder\utils\CallbackEventExe;
use pemapmodder\utils\CallbackPluginTask;

use pocketmine\Player;
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
		$pmgr->registerEvent("pocketmine\\event\\player\\PlayerInteractEvent", $this, EventPriority::LOW, new CallbackEventExe(array($this, "onInteractLP")), HubPlugin::get());
		$pmgr->registerEvent("pocketmine\\event\\entity\\EntityMoveEvent", $this, EventPriority::HIGH, new CallbackEventExe(array($this, "onMove")), HubPlugin::get());
		$pmgr->registerEvent("pocketmine\\event\\player\\PlayerChatEvent", $this, EventPriority::HIGH, new CallbackEventExe(array($this, "onChat")), HubPlugin::get());
	}
	public function onChat(Event $evt){
		$pfxs = HubPlugin::get()->getDb($p = $evt->getPlayer())->get("prefixes");
		$rec = array();
		foreach($evt->getRecipients() as $r){
			if($r->level->getName() === $p->level->getName())
				$rec[] = $r;
		}
		$evt->setRecipients($rec);
		$prefix = "";
		foreach(HubPlugin::getPrefixOrder() as $pfxType=>$filter){
			if(!$this->isFiltered($filter, $p->level->getName()) and \strlen(\str_replace(" ", "", $pfxs[$pfxType])) > 0)
				$prefix .= ($pfxs[$pfxType]."|");
		}
		$format = $prefix."%s: %s";
		$evt->setFormat($format);
	}
	private function isFiltered($filter, $dirt){
		switch($filter){
		case "all":
			return false;
		case "pvp":
			return !in_array($dirt, array("world_pvp"));
		case "pk":
			return !in_array($dirt, array("world_parkour"));
		case "ctf":
			return !in_array($dirt, array("world_tmp_ctf", "world_base_ctf"));
		case "spleef":
			return stripos($dirt, "spleef") === false;
		default: // invalid filter?
			console("[WARNING] Invalid filter: \"$filter\"");
			return false;
		}
	}
	public function onInteractLP(Event $evt){
		$p = $evt->getPlayer();
		if(HubPlugin::getRank($p) !== "staff")
			$evt->setCancelled(true);
	}
	public function onMove(Event $evt){
		$p = $evt->getEntity();
		if(!($p instanceof Player))
			return;
		if(time() - @$this->teleports[strtolower($p->getName())] <= 3)
			return;
		if(RL::enterPvpPor()->isInside($p)){
			$this->server->getScheduler()->scheduleDelayedTask(new CallbackPluginTask(array($p, "teleport"), $this, RL::pvpSpawn()), 40);
			$p->sendMessage("You are going to be teleported to the");
			$p->sendMessage("  PvP world in 2 seconds! You might lag!");
			$this->teleports[strtolower($p->getName())] = time();
		}
		elseif(RL::enterPkPor()->isInside($p)){
			$this->server->getScheduler()->scheduleDelayedTask(new CallbackPluginTask(array($p, "teleport"), $this, RL::pkSpawn()), 40);
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
