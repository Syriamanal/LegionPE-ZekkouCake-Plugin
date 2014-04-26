<?php

namespace pemapmodder\legionpe\hub;

use pemapmodder\legionpe\geog\RawLocs as RL;
use pemapmodder\legionpe\hub\Team;
use pemapmodder\legionpe\mgs\MgMain;

use pemapmodder\utils\CallbackEventExe;
use pemapmodder\utils\CallbackPluginTask;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;

class Hub implements Listener{
	public $server;
	public $teleports = array();
	protected $channels = array();
	private $defaultChannels = array(
		"legionpe.chat.general",
		"legionpe.chat.mute.<CID>",
		"legionpe.chat.team.<TID>",
		"legionpe.chat.pvp.public",
		"legionpe.chat.pvp.<TID>",
		"legionpe.chat.pvp.public",
		"legionpe.chat.pk.<TID>",
		"legionpe.chat.ctf.public",
		"legionpe.chat.ctf.<TID>",
		"legionpe.chat.spleef.public",
		"legionpe.chat.spleef.<TID>",
		"legionpe.chat.spleef.<SID>.<TID>",
		"legionpe.chat.spleef.<SID>");
	public function __construct(){
		$this->server = Server::getInstance();
		$pmgr = $this->server->getPluginManager();
		$pmgr->registerEvent("pocketmine\\event\\player\\PlayerInteractEvent", $this, EventPriority::LOW, new CallbackEventExe(array($this, "onInteractLP")), HubPlugin::get());
		$pmgr->registerEvent("pocketmine\\event\\entity\\EntityMoveEvent", $this, EventPriority::HIGH, new CallbackEventExe(array($this, "onMove")), HubPlugin::get());
		$pmgr->registerEvent("pocketmine\\event\\player\\PlayerChatEvent", $this, EventPriority::HIGH, new CallbackEventExe(array($this, "onChat")), HubPlugin::get());
		$pmgr->registerEvent("pocketmine\\event\\player\\PlayerCommandPreprocessEvent", $this, EventPriority::HIGH, new CallbackEventExe(array($this, "onPreCmd")), HubPlugin::get());
	}
	public function onChat(Event $evt){
		$pfxs = HubPlugin::get()->getDb($p = $evt->getPlayer())->get("prefixes");
		$pfxs["team"] = Team::get(HubPlugin::get()->getDb($p)->get("team"))["name"];
		$rec = array();
		foreach($evt->getRecipients() as $r){
			if($this->getChannel($r) === $this->getChannel($p) or $this->getChannel($p) === "legionpe.chat.mandatory")
				$rec[] = $r;
		}
		$evt->setRecipients($rec);
		$format = $this->getPrefixes($p)."%s: %s";
		$evt->setFormat($format);
	}
	public function onQuitCmd($issuer, array $args){
		// TODO quit
		return true;
	}
	protected function getPrefixes(Player $player){
		$prefix = "";
		foreach(HubPlugin::getPrefixOrder() as $pfxType=>$filter){
			if($pfxType === "team")
				$pf = "".ucfirst(Team::get(HubPlugin::get()->getDb($player)->get("team"))["name"])."";
			else $pf = ucfirst(HubPlugin::get()->getDb($player)->get("prefixes")[$pfxType]);
			if(!$this->isFiltered($filter, $p->level->getName()) and strlen(\str_replace(" ", "", $pf)) > 0)
				$prefix .= "$pf|";
		}
		return $prefix;
	}
	protected function isFiltered($filter, $dirt){
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
			$this->channels[$p->CID] = "legionpe.chat.pvp.public";
		}
		elseif(RL::enterPkPor()->isInside($p)){
			$this->server->getScheduler()->scheduleDelayedTask(new CallbackPluginTask(array($p, "teleport"), $this, RL::pkSpawn()), 40);
			$p->teleport(RL::pkSpawn());
			$p->sendMessage("You are teleported to the");
			$p->sendMessage("  parkour world! You might lag!");
			$this->teleports[strtolower($p->getName())] = time();
			$this->hub->sessions[$p->CID] = HubPlugin::PK;
			$this->channels[$p->CID] = "legionpe.chat.pk.public";
		}
	}
	public function joinMg(Player $p, MgMain $mg){
		
		// TODO: Move to MgMain::onJoinMg(Player)
	}
	public function setChannel(Player $player, $channel = "legionpe.chat.general"){
		$this->channels[$player->CID] = $channel;
	}
	public function getChannel(Player $player){
		return $this->channels[$player->CID];
	}
	public function onPreCmd(Event $event){
		$p = $event->getPlayer();
		$cmd = explode(" ", $event->getMessage());
		$command = substr(array_shift($cmd), 1);
		switch($command){
			case "me":
				$event->setCancelled(true);
				foreach(Player::getAll() as $player){
					if($this->getChannel($player) === $this->getChannel($p) or $this->getChannel($p) === "legionpe.chat.mandatory")
						$player->sendMessage("* {$this->getPrefixes($player)}{$player->getDisplayName()} ".implode(" ", $cmd));
				}
				break;
			case "spawn":
				$event->setCancelled(true);
				$event->getPlayer()->sendMessage("Reminder: use /quit next time!");
				$this->server->dispatchCommand($event->getPlayer(), "quit".substr($event->getMessage(), 1 + 5)); // "/" . "spawn": 1 + 5
				break;
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
