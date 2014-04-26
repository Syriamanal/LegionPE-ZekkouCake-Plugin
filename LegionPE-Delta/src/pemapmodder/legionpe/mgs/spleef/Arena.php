<?php

namespace pemapmodder\legionpe\mgs\spleef;

use pemapmodder\legionpe\hub\HubPlugin;
use pemapmodder\legionpe\hub\Team;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\Position;

class Arena{
	public $hub, $id, $centre, $radius, $height, $floors, $gfloor, $pfloor, $pwall, $pceil;
	public $status = 0, $players = array(), $preps = array();
	public function __construct($id, Position $topCentre, $radius, $height, $floors, $players,
			Block $floor, Block $pfloor, Block $pwall, Block $pceil){
		$this->hub = HubPlugin::get();
		$this->server = Server::getInstance();
		$this->id = $id;
		$this->centre = $topCentre;
		$this->radius = $radius;
		$this->height = $height;
		$this->floors = $floors;
		$this->gfloor = $floor;
		$this->pfloor = $pfloor;
		$this->pwall = $pwall;
		$this->pceil = $pceil;
		$this->build($players);
	}
	protected function build($cnt){
		$this->preps = Builder::build($this->centre, $this->radius, $this->gfloor, $this->floors, $this->height, $cnt, $this->pfloor, $this->pwall, $this->pceil);
	}
	public function join(Player $join){
		if(!$this->canJoin($join))
			return false;
		$this->players[$join->CID] = $join;
		$join->teleport($this->preps[count($this->players) - 1]->add(0.5, 0.5, 0.5));
		return true;
	}
	public function canJoin(Player $join){
		return !($this->status === 1 or count($this->players) >= count($this->preps) or isset($this->players[$join->CID]));
	}
	public function quit(Playet $player, $reason = "command"){
		$this->main->quit($player);
		$db = $this->hub->getDb($player);
		$s = $db->get("spleef");
		$unwons = $s["unwons"];
		$db->set("spleef", $s);
		$db->save();
		Team::get($this->hub->getDb($player)->get("team"))["points"]--;
		$player->sendMessage("You now have $unwons unwon spleef tournament".($s > 1 ? "s":"")."! Team score -1!");
		unset($this->players[$player->CID]);
		foreach($this->players as $p)
			$p->sendMessage("{$player->getDisplayName()} left the spleef tournament due to $reason.");
		if($this->status === 1 and count($this->players) === 1){
			$winner = array_rand($this->players); // since there is only one
			$winner->sendMessage("You won!");
			$db = $this->hub->getDb($winner);
			$w = $db->get("spleef");
			$w["wins"]++;
			$db->set("spleef", $w);
			$db->save();
			$winner->sendMessage("You have won {$w["wins"]} spleef tournament".($w["wins"] > 1 ? "s":"")."! Team score +".($add = count($this->preps))."!");
			Team::get($this->hub->getDb($p)->get("team"))["points"] += ($add * 2);
			$this->main->quit($winner);
			$this->stop();
		}
	}
	public function prestart(){
		$this->server->getScheduler()->scheduleDelayedTask(new CallbackPluginTask(array($this, "start"), $this->hub), 200);
	}
	public function start(){
		$this->build(count($this->preps));
		// TODO broadcast
	}
	public function end($reason = ""){
		// TODO end
	}
	public function broadcast($message, $ret = null){
		foreach($this->players as $p)
			$p->sendMessage($message);
		return $ret;
	}
	public function onInteract($event){
		$event->setCancelled(true);
		if(mt_rand(1, 100) <= $this->hub->config->get("spleef")["chances"][$this->hub->getRank($event->getPlayer())] and $event->getBlock()->getID() === $this->gfloor->getID()){
			$event->getBlock()->level->setBlock($event->getBlock(), Block::get(0), false, false, true);
		}
	}
	public function onMove($event){
		$player = $event->getEntity();
		if($this->status === 0){
			$event->setCancelled(true);
			foreach($this->players as $CID=>$p){
				$p->teleport($this->preps[array_search($CID, array_keys($this->players))]);
			}
		}else{
			if($this->deadzone->isInside($player)){
				$player->sendMessage("You lost!");
				$player->teleport(Level::get("world_spleef"));
				$this->quit($player, "falling");
			}
		}
	}
}
