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
	public $hub, $id, $centre, $radius, $height, $floors;
	protected $gfloor, $pfloor, $pwall, $pceil;
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
	public function cntPlayers(){
		return count($this->players);
	}
	public function maxPlayers(){
		return count($this->preps);
	}
	protected function build($cnt){
		$this->preps = Builder::build($this->centre, $this->radius, $this->gfloor, $this->floors, $this->height, $cnt, $this->pfloor, $this->pwall, $this->pceil);
	}
	public function broadcast($message, $ret = null){
		foreach($this->players as $p)
			$p->sendMessage($message);
		return $ret;
	}
}
