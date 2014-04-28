<?php

namespace pemapmodder\legionpe\hub;

use pemapmodder\legionpe\geog\RawLocs as RL;
use pemapmodder\legionpe\hub\Team;
use pemapmodder\legionpe\mgs\MgMain;
use pemapmodder\legionpe\mgs\pvp\Pvp;

use pemapmodder\utils\CallbackEventExe;
use pemapmodder\utils\CallbackPluginTask;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\commamd\Command;
use pocketmine\command\CommandExecutor as CmdExe;
use pocketmine\command\CommandSender as Issuer;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;

class Hub implements CmdExe, Listener{
	public $server;
	public $teleports = array();
	protected $channels = array();
	private $defaultChannels = array(
		"legionpe.chat.general", // This format familiar? Yes, I wanted to make them permissions but ended up using them purely as strings
		"legionpe.chat.mute.<CID>",
		"legionpe.chat.team.<TID>",
		"legionpe.chat.pvp.public",
		"legionpe.chat.pvp.<TID>",
		"legionpe.chat.pk.public",
		"legionpe.chat.pk.<TID>",
		"legionpe.chat.ctf.public",
		"legionpe.chat.ctf.<TID>",
		"legionpe.chat.spleef.public",
		"legionpe.chat.spleef.<TID>",
		"legionpe.chat.spleef.<SID>.<TID>",
		"legionpe.chat.spleef.<SID>");
	public function __construct(){
		$this->server = Server::getInstance();
		$this->hub = HubPlugin::get();
		$pmgr = $this->server->getPluginManager();
		$pmgr->registerEvent("pocketmine\\event\\player\\PlayerInteractEvent", $this, EventPriority::LOW, new CallbackEventExe(array($this, "onInteractLP")), HubPlugin::get());
		$pmgr->registerEvent("pocketmine\\event\\entity\\EntityMoveEvent", $this, EventPriority::HIGH, new CallbackEventExe(array($this, "onMove")), HubPlugin::get());
		$pmgr->registerEvent("pocketmine\\event\\player\\PlayerChatEvent", $this, EventPriority::HIGH, new CallbackEventExe(array($this, "onChat")), HubPlugin::get());
		$pmgr->registerEvent("pocketmine\\event\\player\\PlayerQuitEvent", $this, EventPriority::HIGH, new CallbackEventExe(array($this, "onQuit")), HubPlugin::get());
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
	public function onQuit(Event $event){
		if(($s = $this->hub->sessions[$event->getPlayer()->CID]) > HubPlugin::HUB and $s <= HubPlugin::ON)
			$this->server->dispatchCommand($event->getPlayer(), "quit");
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
		if(time() - @$this->teleports[$p->CID] <= 3)
			return;
		if(RL::enterPvpPor()->isInside($p)){
			$this->joinMg($p, Pvp::get());
		}
		elseif(RL::enterPkPor()->isInside($p)){
			$this->joinMg($p, Parkour::get());
		}
	}
	protected function joinMg(Player $p, MgMain $mg){
		$TID = $this->hub->getDb($p)->get("team");
		if(($reason = $mg->isJoinable($p, $TID)) === true){
			$this->server->getScheduler()->scheduleDelayedTask(
					new CallbackPluginTask(array($p, "teleport"), $this->hub, $mg->getSpawn($p, $TID)), 40);
			$p->teleport($mg->getSpawn($p, $TID));
			$p->sendMessage("You are teleported to the");
			$p->sendMessage("  ".$mg->getName()." world! You might lag!");
			$this->teleports[$p->CID] = time();
			$this->hub->sessions[$p->CID] = $mg->getSessionId();
			$this->setChannel($p, $mg->getDefaultChatChannel($p, $TID));
			$mg->onJoinMg($p);
		}else{
			$p->sendMessage("{$mg->getName()} cannot be joined currently due to $reason!");
			$p->teleport(RL::spawn());
		}
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
	public function onCommand(Issuer $isr, Command $cmd, $lbl, array $args){
		switch($cmd->getName()){
			case "chat":
				switch($subcmd = array_shift($args)){
					case "ch":
						if(!$isr->hasPermission("legionpe.cmd.chat.ch"))
							return "You don't have permission to use /chat ch";
						if(!isset($args[0]))
							return false;
						$ch = array_shift($args);
						if(!in_array($ch, $this->defaultChannels))
							return "Channel $ch does not exist!";
						if($this->hasChannelPermission($this->hub->getSession($isr), $ch, $isr))
							$this->setChannel($isr, $ch);
						elseif($isr->hasPermission("legionpe.cmd.chat.ch.all"))
							$this->setChannel($isr, $ch);
						else return "You don't have permission to join this chat channel";
						return "Your chat channel has been set to \"$ch\"";
				}
		}
	}
	public function hasChannelPermission($s, &$ch, Issuer $player){
		if(!($player instanceof Player)){
			return true;
		}
		if(strpos("mandatory", $ch) !== false){
			return false;
		}
		$tid = $this->hub->getDb($player)->get("team")
		switch($s){
			case HubPlugin::PVP:
				$mg = "pvp";
			case HubPlugin::PK:
				if(!isset($mg)) $mg = "pk";
				switch($ch){
					case "$mg.public":
					case "legionpe.chat.$mg.public":
					case "chat.$mg.public":
						$ch = "legionpe.chat.$mg.public";
						return true;
					case "$mg.team":
					case "chat.$mg.team":
					case "legionpe.chat.$mg.team":
					case "leginope.chat.$mg.team.$tid":
						$ch = "legionpe.chat.$mg.$tid";
						return true;
				}
			case HubPlugin::HUB:
				switch($ch){
					case "general":
					case "chat.general":
					case "legionpe.chat.general":
						$ch = "legionpe.chat.general";
						return true;
					case "mute":
					case "chat.mute":
					case  "legionpe.chat.mute":
						$ch = "legionpe.chat.mute";
						return true;
					case "team":
					case "chat.team":
					case "legionpe.chat.team":
					case "legionpe.chat.team.$tid":
						$ch = "legionpe.chat.team.$tid";
						return true;
				}
			default:
				return false;
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
