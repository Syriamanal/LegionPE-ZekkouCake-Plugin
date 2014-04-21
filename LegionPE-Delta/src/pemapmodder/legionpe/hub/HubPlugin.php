<?php

namespace pemapmodder\legionpe\hub;

use pemapmodder\legionpe\geog\RawLocs as Loc;
use pemapmodder\legionpe\mgs\pvp\Pvp;

use pemapmodder\utils\CallbackPluginTask;
use pemapmodder\utils\CallbackEventExe;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\event\Event;
use pocketmine\event\Listener;
use pocketmine\level\Level;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class HubPlugin extends PluginBase implements Listener{
	const REGISTER	= 0b10;
	const ONLINE	= 0b111;
	const LOGIN		= 0b1000;
	const LOGIN_MAX	= 0b1111;
	protected $sessions = array();
	protected $tmpPws = array();
	public function onLoad(){
		self::$instance = $this;
		$this->path = $this->getServer()->getDataPath()."Hub/";
		@mkdir($this->path);
		$this->playerPath = $this->path."players/";
		@mkdir($this->playerPath);
	}
	public function onEnable(){
		console(TextFormat::AQUA."Initializing Hub... ", false);
		$this->registerHandles();
		$this->initObjects();
		$this->initCmds();
		console(TextFormat::GREEN."Done!");
	}
	protected function initObjects(){
		Team::init();
		Hub::init();
		Pvp::init();
	}
	protected function registerHandles(){
		foreach(array("PlayerJoin", "PlayerChat", "EntityArmorChange", "EntityMove", "PlayerInteract", "PlayerCommandPreprocess", "PlayerLogin") as $e)
			$this->addHandler($e);
	}
	protected function addHandler($event){
		$this->getServer()->getPluginManager()->registerEvent(
				"pocketmine\\event\\".substr(strtolower($event), 0, 6)."\\".$event."Event", $this,
				EventPriority::HIGH, new CallbackEventExe(array($this, "evt")), $this, false);
	}
	public function initCmds(){
		$cmd = new PluginCommand("show", $this);
		$cmd->setUsage("/show <invisible player|all>");
		$cmd->setDescription("Attempt to show an invisible player");
		$cmd->register($this->getServer()->getCommandMap());
	}
	public function onCommand(CommandSender $issuer, Command $cmd, $label, array $args){
		switch($cmd->getName()){
		case "show":
			if(!($issuer instanceof Player)){
				$issuer->sendMessage("You are not supposed to see any players here!");
				return true;
			}
			if(@strtolower(@$args[0]) !== "all" and !(($p = Player::get(@$args[0])) instanceof Player))
				return false;
			if(strtolower($args[0]) !== "all"){
				if($p->level->getName() === $issuer->level->getName())
					$p->spawnTo($issuer);
				else $issuer->sendMessage($p->getDisplayName()." is not in your world!");
			}
			else{
				foreach(Player::getAll() as $p){
					if($p->level->getName() === $issuer->level->getName())
						$p->spawnTo($issuer);
				}
			}
			return true;
		}
		return true;
	}
	public function evt(Event $event){
		$class = explode("\\", get_class($event));
		$class = $class[count($class) - 1];
                if(is_callable(array($event, "getPlayer")))
                    $p = $event->getPlayer();
		switch(substr($class, 0, -5)){
			case "PlayerLogin":
				break;
			case "PlayerJoin":
				$event->setMessage("");
				$this->openDb($p);
				if($this->getDb($p)->get("pw-hash") === false){
					$this->sessions[$p->getName()] = self::REGISTER;
					$p->sendMessage("Welcome to the LegionPE account registry wizard.");
					$p->sendMessage("Step 1:");
					$p->sendMessage("Please type your password in chat and send it. Don't worry, other players won't be able to read it.");
				}
				elseif($this->getDb($p)->get("ip-auth") === $p->getAddress()){
					$p->sendMessage("You have been authenticated by your IP address.");
					$this->sessions[$p->getName()] = self::ONLINE;
					$this->onAuthPlayer($p);
				}
				else{
					$p->sendMessage("Please type your password in chat and send it. Don't worry, other players won't be able to read it.");
					$this->sessions[$p->getName()] = self::LOGIN;
				}
				break;
			case "PlayerChat":
				if(($s = $this->sessions[$p->getName()]) !== 0b100)
					$event->setCancelled(true);
				if($s === self::REGISTER){
					$this->tmpPws[$p->getName()] = $event->getMessage();
					$p->sendMessage("Step 2:");
					$p->sendMessage("Please enter your password again to confirm.");
					$this->sessions[$p->getName()] ++;
				}
				if($s === self::REGISTER + 1){
					if($this->tmpPws[$p->getName()] === $event->getMessage()){
						$p->sendMessage("The password matches! Type this password into your chat and send it next time you login.");
						$p->sendMessage("LegionPE registry wizard closed!"); // TODO anything else I need to check?
						$this->getDb($p)->set("pw-hash", $this->hash($event->getMessage()));
						$this->sessions[$p->getName()] = self::ONLINE;
						unset($this->tmpPws[$p->getName()]);
						$this->onRegistered($p);
					}
					else{
						$p->sendMessage("Password doesn't match! Going back to step 1.");
						$p->sendMessage("Please type your password in the chat.");
						$this->sessions[$p->getName()] = self::REGISTER;
					}
				}
				else{
					$hash = $this->getDb($p)->get("pw-hash");
					if($this->hash($event->getMessage()) === $hash){
						$this->onAuthPlayer($p);
					}
					else{
						$p->sendMessage("Password doesn't match! Please try again.");
						$this->sessions[$p->getName()] ++;
						if($s >= self::LOGIN_MAX){
							$p->sendMessage("You exceeded the max number of trials to login! You are being kicked.");
							$this->getServer()->getScheduler()->scheduleDelayedTask(
									new CallbackPluginTask(array($p, "close"), $this, array("Failing to auth.", "Auth failure"), true), 80);
						}
					}
				}
				break;
			case "EntityArmorChange":
			case "EntityMove":
				$p = $event->getEntity();
				if(!($p instanceof Player))
					break;
			case "PlayerCommandPreprocess":
			case "PlayerInteract":
				if($this->sessions[$p->getname() !== self::ONLINE){
					$event->setCancelled(true);
					$p->sendChat("Please login/register first!");
				}
				break;
			default:
				console("[WARNING] Event ".get_class($event)." passed to listener at ".get_class()." but not listened to!");
				break;
		}
	}
	public function onRegistered(Player $p){
		$p->teleport(Loc::chooseTeamStd());
		$p->sendChat("Please select a team.\nSome teams are unselectable because they are too full.\nIf you insist to join those teams, come back later.");
	}
	public function onAuthPlayer(Player $p){
		$p->sendChat("You have successfully logged in into LegionPE!");
		$s = Level::get("world")->getSafeSpawn();
		$p->teleport($s);
		$this->getServer()->getPluginManager()->callEvent(new PlayerAuthEvent($p));
		$this->getServer()->getScheduler()->scheduleDelayedTask(
				new CallbackPluginTask(array($p, "teleport"), $this, array($s), true), 100);
	}
	// local utils //
	private function openDb($p){
		$config = new Config($this->playerPath.substr(strtolower($p->getName()), 0, 1)."/".strtolower($p->getName()), Config::YAML, array(
			"pw-hash" => false,
			"ip-auth" => false,
			"prefixes" => array("kitpvp"=>"", "parkour"=>"", "kitpvp-rank"=>""),
			"individuals" => array(),
		));
		$this->dbs[strtolower($p->getName())] = $config;
	}
	private function getDb($p){
		return $this->dbs[strtolower($p->getName())];
	}
	public function hash($string){
		$salt = "";
		for($i = strlen($string) - 1; $i >= 0; $i--)
			$salt .= $string{$i};
		$salt = crypt($string, $salt);
		return bin2hex(hash(hash_algos()[17], $string.$salt, true) ^ hash(hash_algos()[31], strtolower($salt).$string, true));
	}
	public static $instance = false;
	public static function get(){
		return self::$instance;
	}
}
