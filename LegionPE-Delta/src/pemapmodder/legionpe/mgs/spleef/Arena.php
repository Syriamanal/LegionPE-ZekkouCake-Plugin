<?php

namespace pemapmodder\legionpe\mgs\spleef;

use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\level\Position;

class Arena{
	public function __construct(Position $topCentre, $radius,
			$height, $floors, $players,
			Block $floor, Block $pfloor, Block $pwall, Block $pceil){
		$this->centre = $topCentre;
		$this->radius = $radius;
		$this->height = $height;
		$this->floors = $floors;
		$this->gfloor = $floor;
		$this->pfloor = $pfloor;
		$this->pwall = $pwall;
		$this->pceil = $pceil;
		$this->status = 0;
		$this->players = array();
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
	public function onInteract($event){
	}
	public function onMove($event){
	}
}
