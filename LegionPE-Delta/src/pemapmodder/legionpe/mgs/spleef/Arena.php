<?php

namespace pemapmodder\legionpe\mgs\spleef;

use pemapmodder\legionpe\hub\HubPlugin;

use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\Position;

class Arena{
	public $hub, $id, $centre, $radius, $height, $floors, $gfloor, $pfloor, $pwall, $pceil;
	public $status = 0, $players = array(), $preps = array();
	public function __construct($id, Position $topCentre, $radius, $height, $floors, $players,
			Block $floor, Block $pfloor, Block $pwall, Block $pceil){
		$this->hub = HubPlugin::get();
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
		if($this->status === 1 or count($this->players) >= count($this->preps) or isset($this->players[$join->getCID(}]))
			return false;
		$this->players[$join->getCID()] = $join;
		$join->teleport($this->preps[count($this->players) - 1]->add(0.5, 0.5, 0.5));
	}
	public function quit(Playet $player, $reason = "logout"){
		$this->main->quit($player);
		$db = $this->hub->getDb($player);
		$s = $db->get("spleef");
		$unwons = $s["unwons"];
		$db->set("spleef", $s);
		$db->save();
		
		$player->sendMessage("You now have $unwons unwon spleef tournament".($s > 1 ? "s":"")."!");
		unset($this->players[$player->getCID()]);
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
			$winner->sendMessage("You have won {$w["wins"]} spleef tournament".($w["wins"] > 1 ? "s":"")."!");
			$this->main->quit($winner);
			$this->stop();
		}
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
