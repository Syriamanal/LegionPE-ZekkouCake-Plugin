<?php

namespace pemapmodder\legionpe\mgs\spleef;

use pemapmodder\legionpe\hub\Hub;
use pemapmodder\legionpe\hub\HubPlugin;
use pemapmodder\legionpe\mgs\MgMain;

use pemapmodder\utils\CallbackEventExe;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\block\Block;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;

class Main implements Listener, MgMain{
	public $arenas = array();
	public $sessions = array();
	protected $atchmts = array();
	public function __construct(){
		$this->hub = HubPlugin::get();
		// TODO initialize arenas with raw coords data
		// E.g.:
		/*
		for($id = 0; $id < $max; $id++){
			$centre = $this->getCentreById();
			$this->arenas[$id] = new Arena($id, $centre, 10, 4, 8, Block::get(80), Block::get(7), Block::get(20), Block::get(20));
		}
		*/
		$this->server = Server::getInstance();
		$pm = $this->server->getPluginManager();
		foreach(array(
				array("entity\\EntityMoveEvent", "onMove"),
				array("player\\PlayerInteractEvent", "onInteract"),) as $ev)
			$pm->registerEvent("pocketmine\\event\\".$ev[0], $this, EventPriority::HIGH, new CallbackEventExe(array($this, $ev[1])), HubPlugin::get());
	}
	public function onMove(Event $evt){
		if($evt->getEntity() instanceof Player){
			if(($sid = $this->sessions[$evt->getEntity()->CID]) !== -1)
				$this->arenas[$sid]->onMove($evt);
		}
	}
	public function onInteract(Event $evt){
		if(($sid = $this->sessions[$evt->getPlayer()->CID]) !== -1)
			$this->arenas[$sid]->onInteract($evt);
		else{
			for($i = 1; $i <= 4; $i++){
				if(Builder::signs($i)->isInside($evt->getBlock())){
					$this->join($i, $evt->getPlayer());
					break;
				}
			}
		}
	}
	public function join($SID, Player $player){
		if($this->arenas[$SID]->isJoinable()){
			$this->arenas[$SID]->join($player);
		}
		else{
			$player->sendMessage("The match has started / waiting to start! You can't join!");
		}
	}
	public function quit($from, Player $player){
		$isTeam = count(explode(".", Hub::get()->getChannel($player))) === 5;
		Hub::get()->setChannel($player, $isTeam ? "legionpe.chat.spleef.".$this->hub->getDb($player)->get("team"):"legionpe.chat.spleef.public");
	}
	public function getChance(Player $player){
		return $this->hub->config->get("spleef")["chances"][$this->hub->getRank($player)];
	}
	public function onJoinMg(Player $p){
		$this->sessions[$p->CID] = -1;
		$this->atchmts[$p->CID] = $p->addAttachment($this->hub, "legionpe.cmd.mg.spleef", true);
	}
	public function onQuitMg(Player $p){
		if(!isset($this->sessions[$p->CID])) return;
		if(($s = $this->sessions[$p->CID]) !== -1){
			$this->arenas[$sid]->quit($event->getPlayer(), "logout");
		}
		unset($this->sessions[$p->CID]);
		$p->removeAttachment($this->atchmts[$p->CID]);
		unset($this->atchmts[$p->CID]);
	}
	public function getName(){
		return "Spleef";
	}
	public function getSessionId(){
		return HubPlugin::SPLEEF;
	}
	public function getSpawn(){
		return Builder::spawn();
	}
	public function getDefaultChatChannel(){
		return "legionpe.chat.spleef.public";
	}
	public function isJoinable(){
		return true;
	}
	public static $instance = false;
	public static function get(){
		return self::$instance;
	}
	public static function init(){
		self::$instance = new self();
	}
}
