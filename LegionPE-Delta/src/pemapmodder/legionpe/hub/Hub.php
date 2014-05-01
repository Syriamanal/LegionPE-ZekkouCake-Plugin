<?php

namespace pemapmodder\legionpe\hub;

use pemapmodder\legionpe\geog\RawLocs as RL;
use pemapmodder\legionpe\hub\Team;
use pemapmodder\legionpe\mgs\MgMain;
use pemapmodder\legionpe\mgs\pvp\Pvp;
use pemapmodder\legionpe\mgs\pk\Parkour as Parkour;
use pemapmodder\legionpe\mgs\spleef\Main as Spleef;

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
		$output = "";
		switch($cmd->getName()){
			case "mute":
			case "unmute":
				array_unshift($args, $cmd->getName());
			case "chat":
				switch($subcmd = array_shift($args)){
					case "mute":
					case "unmute":
						array_unshift($args, $subcmd);
					case "ch":
						if(!$isr->hasPermission("legionpe.cmd.chat.ch"))
							return "You don't have permission to use /chat ch";
						if(isset($args[0])){
							$ch = array_shift($args);
							if($isr->hasPermission("legionpe.cmd.chat.ch.all")){
								$copy = $ch;
								if($this->proofreadChannelName($ch)){
									if($copy !== "mute" and $copy !== "m")
										$this->hub->getDb($isr)->set("mute", false);
									$this->hub->getDb($isr)->save();
									return "Typo detected in \"$ch\".";
								}
								$this->setChannel($isr, $ch); // absolute channel
							}
							elseif($this->hasChannelPermission($this->hub->getSession($isr), $ch, $isr)){
								$this->setChannel($isr, $ch);
							}
							else return "You don't have permission to create/join this chat channel";
							return "Your chat channel has been set to \"$ch\"";
						}
				}
			case "help":
				$output = "Showing help of /chat, /mute and /unmute:\n";
			default:
				$output .= "/unmute: Equal to /chat unmute";
				$output .= "/mute: Equal to /chat mute";
				$output .= "/chat mute: Equal to \"/chat ch m\" or \"/chat ch mute\"";
				$output .= "/chat ch <channel> Join a chat channel"
		}
	}
	public function proofreadChannelName(&$ch){ // check if typo exists; not very safe, but at least a safeguard exists
		$ch = strtolower($ch);
		$tokens = explode(".", $ch);
		if($tokens[0] !== "legionpe" or $tokens[1] !== "chat")
			return true;
		while(count($tokens) > 0){
			$token = array_shift($tokens);
			if(!in_array($token, array("legionpe", "pvp", "pk", "spleef", "ctf", "general", "chat", "public", "mute", "team")) and !is_numeric($token)){
				return true;
			}
		}
		return false;
	}
	public function hasChannelPermission($s, &$ch, Issuer $player){
		if(!($player instanceof Player)){
			return true;
		}
		if(strpos("mandatory", $ch) !== false){
			return false;
		}
		$tid = $this->hub->getDb($player)->get("team");
		switch($ch){
			case "p":
			case "public":
				switch($s){
					case HubPlugin::HUB:
						$ch = "legionpe.chat.general";
						return true;
					case HubPlugin::PVP:
						$ch = "leginope.chat.pvp.public";
						return true;
					case HubPlugin::PK:
						$ch = "legionpe.chat.pk.public";
						return true;
					case HubPlugin::SPLEEF:
						$ch = "legionpe.chat.spleef.public";
						return true;
					case HubPlugin::CTF:
						$ch = "legionpe.chat.ctf.public";
						return true;
					default:
						return false;
				}
			case "u":
			case "unmute":
				$ch = $this->getDefaultChannel($player);
				return true;
			case "m":
			case "mute":
				$this->hub->getDb($player)->set("mute", true);
				$ch = "legionpe.chat.mute.".$player->CID;
				return true;
			case "t":
			case "team":
				switch($s){
					case HubPlugin::HUB:
						$ch = "legionpe.chat.team.$tid";
						return true;
					case HubPlugin::PVP:
						$ch = "leginope.chat.pvp.$tid";
						return true;
					case HubPlugin::PK:
						$ch = "legionpe.chat.pk.$tid";
						return true;
					case HubPlugin::SPLEEF:
						$ch = "leginope.chat.spleef.$tid";
						return true;
					case HubPlugin::CTF:
						$ch = "legionpe.chat.ctf.$tid";
						return true;
					default:
						return false;
				}
			case "s":
				$nt = true;
			case "st":
				if($s === HubPlugin::SPLEEF and ($sid = Spleef::getSession($player)) !== false){
					$ch = "legionpe.chat.spleef.$sid";
					if(!isset($nt))
						$ch .= ".$tid";
					return true;
				}
				return false;
			case "g":
			case "gen":
			case "general":
				$ch = "legionpe.chat.general";
				return true;
			default:
				return false;
		}
	}
	protected function getDefaultChannel(Player $player){
		$t = $this->hub->getDb($player)->get("team");
		switch($this->hub->getSession($player)){
			case HubPlugin::HUB:
				return "legionpe.chat.general";
			case HubPlugin::PVP:
				$c = "pvp\\Pvp";
				break;
			case HubPlugin::PK:
				$c = "pk\\Parkour";
				break;
			case HubPlugin::SPLEEF:
				$c = "spleef\\Main";
			case HubPlugin::CTF:
				$c = "ctf\\Main";
			default:
				return "legionpe.chat.mute.".$player->CID;
		}
		$c = "pemapmodder\\legionpe\\mgs\\$c";
		return $c::get()->getDefaultChatChannel($player, $t);
	}
	public static $inst = false;
	public static function init(){
		self::$inst=new self();
	}
	public static function get(){
		return self::$inst;
	}
}
