<?php

namespace pemapmodder\legionpe\hub;

use pemapmodder\legionpe\geog\RawLocs as RL;
use pemapmodder\legionpe\hub\Team;

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
		$pfxs["team"] = Team::get(HubPlugin::get()->getDb($p)->get("team"))["name"];
		$rec = array();
		foreach($evt->getRecipients() as $r){
			if($r->level->getName() === $p->level->getName())
				$rec[] = $r;
		}
		$evt->setRecipients($rec);
		$prefix = "";
		foreach(HubPlugin::getPrefixOrder() as $pfxType=>$filter){
			$pf = ucfirst($pfxs[$pfxType]);
			if(!$this->isFiltered($filter, $p->level->getName()) and \strlen(\str_replace(" ", "", "$pf")) > 0)
				$prefix .= "$pf|";
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
			$p->teleport(RL::pvpSpawn());
			$p->sendMessage("You are teleported to the");
			$p->sendMessage("  PvP world! You might lag!");
			$this->teleports[strtolower($p->getName())] = time();
			$this->hub->sessions[$p->CID] = HubPlugin::PVP;
		}
		elseif(RL::enterPkPor()->isInside($p)){
			$this->server->getScheduler()->scheduleDelayedTask(new CallbackPluginTask(array($p, "teleport"), $this, RL::pkSpawn()), 40);
			$p->teleport(RL::pkSpawn());
			$p->sendMessage("You are teleported to the");
			$p->sendMessage("  parkour world! You might lag!");
			$this->teleports[strtolower($p->getName())] = time();
			$this->hub->sessions[$p->CID] = HubPlugin::PK;
		}
	}
	public static $inst = false;
	public static function init(){
		self::$inst=new self();
	}
	public static function get(){
		return self::$inst;
	}
}
