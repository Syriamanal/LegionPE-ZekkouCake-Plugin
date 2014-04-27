<?php

namespace pemapmodder\legionpe\mgs\pk;

use pemapmodder\legionpe\hub\HubPlugin;
use pemapmodder\legionpe\mgs\MgMain;

use pemapmodder\utils\CallbackEventExe;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\block\SignPost;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;

class Parkour implements Listener, MgMain{
	protected $prefixes = array(
		0=>"easy",
		1=>"medium",
		2=>"hard",
		3=>"extreme"
	);
	public function __construct(){
		$this->server = Server::getInstance();
		$pm = $this->server->getPluginManager();
		$pm->registerEvent("pocketmine\\event\\entity\\EntityMoveEvent", $this, EventPriority::HIGH, new CallbackEventExe(array($this, "onMove")), HubPlugin::get());
		$pm->registerEvent("pocketmine\\event\\player\\PlayerInteractEvent", $this, EventPriority::HIGH, new CallbackEventExe(array($this, "onInteract")), HubPlugin::get());
	}
	public function onMove(Event $event){
		if(($p = $event->getEntity()) instanceof Player){
			if($p->level->getName() === "world_parkour"){
				if($p->y <= RawLocs::fallY())
					$p->teleport(RawLocs::pk()->getSafeSpawn());
			}
		}
	}
	public function onInteract(Event $event){
		if($event->getBlock() instanceof SignPost){
			$event->setCancelled(true);
			if(($pfx = RawLocs::signPrefix($event->getBlock())) !== false){
				$config = HubPlugin::get()->getDb($event->getPlayer());
				$prefixes = $config->get("prefixes");
				if(array_search($prefixes["parkour"], $this->prefixes) >= array_search($pfx, $this->prefixes)){
					$event->getPlayer()->sendMessage("You can't set your prefix to a lower level!\nYou (might have) spent so much effort getting \"".$prefixes["parkour"]."\".\nWhy give it up?");
					return;
				}
				$prefixes["parkour"] = $pfx;
				$config->set("prefixes", $prefixes);
				$config->save();
			}
		}
	}
	public function onJoinMg(Player $p){
	}
	public function onQuitMg(Player $p){
	}
	public function getName(){
		return "Parkour";
	}
	public function getSessionId(){
		return HubPlugin::PK;
	}
	public function getDefaultChatChannel(Player $p, $t){
		return "legionpe.chat.pk.public";
	}
	public static $i = false;
	public static function get(){
		return self::$i;
	}
	public static function init(){
		self::$i = new self();
	}
}
