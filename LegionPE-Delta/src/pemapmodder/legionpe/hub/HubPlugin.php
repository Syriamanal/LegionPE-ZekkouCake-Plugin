<?php

namespace pemapmodder\legionpe\hub;

use pemapmodder\legionpe\geog\RawLocs as Loc;
use pemapmodder\legionpe\geog\Position as MyPos;
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
				EventPriority::HIGHEST, new CallbackEventExe(array($this, "evt")), $this, false);
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
			break;
		case "hide":
			if(!($issuer instanceof Player)){
				$issuer->sendMessage("You are not supposed to see any players here!");
				return true;
			}
			if(!isset($args[0]) or !(($p = Player::get($args[0])) instanceof Player))
				return false;
			$p->despawnFrom($issuer);
			$issuer->sendMessage("You can no longer see ".$p->getDisplayName()." now.");
			break;
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
				// console("[INFO] ".$p->getDisplayName()." entered the game.");
				$event->setMessage("");
				$this->openDb($p);
				if($this->getDb($p)->get("pw-hash") === false){ // request register (LegionPE registry wizard), if password doesn't exist
					$this->sessions[$p->getName()] = self::REGISTER;
					$p->sendMessage("Welcome to the LegionPE account registry wizard.");
					$p->sendMessage("Step 1:");
					$p->sendMessage("Please type your password in chat and send it. Don't worry, other players won't be able to read it.");
				}
				elseif($this->getDb($p)->get("ip-auth") === $p->getAddress()){ // authenticate, if ip auth enabled and matches
					$p->sendMessage("You have been authenticated by your IP address.");
					$this->sessions[$p->getName()] = self::ONLINE;
					$this->onAuthPlayer($p);
				}
				else{ // request login (normal), if password exists and ip auth not enabled or not matched
					$p->sendMessage("Please type your password in chat and send it. Don't worry, other players won't be able to read it.");
					$this->sessions[$p->getName()] = self::LOGIN;
				}
				break;
			case "PlayerChat":
				if(($s = $this->sessions[$p->getName()]) !== 0b100) // if not authed
					$event->setCancelled(true);
				elseif($this->getDb($p)->get("pw-hash") === $this->hash($event->getMessage())){ // if authed but is telling password
					$event->setCancelled(true);
					$p->sendMessage("Never talk loudly to others your password!");
				}
				if($s === self::REGISTER){ // request repeat password: registry wizard step 1
					$this->tmpPws[$p->getName()] = $event->getMessage();
					$p->sendMessage("Step 2:");
					$p->sendMessage("Please enter your password again to confirm.");
					$this->sessions[$p->getName()] ++;
				}
				elseif($s === self::REGISTER + 1){ // check repeated password: registry wizard step 2
					if($this->tmpPws[$p->getName()] === $event->getMessage()){ // choose team, if matches password
						$p->sendMessage("The password matches! Type this password into your chat and send it next time you login.");
						$p->sendMessage("LegionPE registry wizard closed!"); // TODO anything else I need to request?
						$this->getDb($p)->set("pw-hash", $this->hash($event->getMessage()));
						$this->sessions[$p->getName()]++;
						unset($this->tmpPws[$p->getName()]);
						$this->onRegistered($p);
					}
					else{ // if password different
						$p->sendMessage("Password doesn't match! Going back to step 1.");
						$p->sendMessage("Please type your password in the chat.");
						$this->sessions[$p->getName()] = self::REGISTER;
					}
				}
				elseif($s === self::LOGIN){ // check password, if session is waiting login
					$hash = $this->getDb($p)->get("pw-hash");
					if($this->hash($event->getMessage()) === $hash){ // auth, if password matches
						$this->onAuthPlayer($p);
					}
					else{ // add session, if password doesn't match
						$p->sendMessage("Password doesn't match! Please try again.");
						$this->sessions[$p->getName()] ++;
						if($s >= self::LOGIN_MAX){ // if reaches maximum trials of login
							$p->sendMessage("You exceeded the max number of trials to login! You are being kicked.");
							$this->getServer()->getScheduler()->scheduleDelayedTask(
									new CallbackPluginTask(array($p, "close"), $this, array("Failing to auth.", "Auth failure"), true), 80);
						}
					}
				}
				break;
			// protect|block player whilst logging in/registering
			case "EntityArmorChange":
			case "EntityMove":
				$p = $event->getEntity();
				if(!($p instanceof Player))
					break;
			case "PlayerCommandPreprocess":
			case "PlayerInteract":
				if($this->sessions[$p->getName() !== self::ONLINE){
					$event->setCancelled(true);
					$p->sendMessage("Please login/register first!");
				}
				elseif($event instanceof \pocketmine\event\player\PlayerInteractEvent){ // check if is tapping join team signs, if is block touch
					$block = new MyPos($event->getBlock());
					if($block->level->getName() === Loc::hub()->getName()){
						for($i = 0; $i < 4; $i++){
							if($block->equals(Loc::chooseTeamSign($i))){
								if($this->getDb($p)->get("team") === false){
									$team = Team::get($i);
									if(($reason = $team->join($p)) === "SUCCESS"){
										$this->getDb($p)->set("team", $i);
										$p->sendMessage("$reason! You are now a member of team $team!");
										$p->teleport(Loc::spawn());
										$this->onAuthPlayer($p);
									}
									else{
										$p->sendMessage("Failure to join team $team. Reason: $reason");
									}
								}
								break;
							}
						}
					}
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
		$this->sessions[$p->getName()] = self::ONLINE;
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
			"team" => false,
		));
		$this->dbs[strtolower($p->getName())] = $config;
	}
	private function getDb($p){
		if(is_string($p))
			$iname = strtolower($p);
		else $iname = strtolower($p->getName());
		return $this->dbs[$iname];
	}
	public function hash($string){
		$salt = "";
		for($i = strlen($string) - 1; $i >= 0; $i--)
			$salt .= $string{$i};
		$salt = @crypt($string, $salt);
		return bin2hex((0xdeadc0de * hash(hash_algos()[17], $string.$salt, true)) ^ (0x6a7e1ee7 * hash(hash_algos()[31], strtolower($salt).$string, true)));
	}
	public static $instance = false;
	public static function get(){
		return self::$instance;
	}
}
