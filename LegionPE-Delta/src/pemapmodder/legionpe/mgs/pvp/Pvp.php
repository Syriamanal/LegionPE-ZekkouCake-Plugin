<?php

namespace pemapmodder\legionpe\mgs\pvp;

use pemapmodder\utils\CallbackEventExe as EvtExe;
use pemapmodder\utils\CallbackPluginTask as Task;

use pocketmine\Server;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;

class Pvp implements Listener{
	public function __construct(){
		$this->server = Server::getInstance();
		// $this->server->getPluginManager()->registerEvent("pocketmine\\event\\player\\PlayerDeathEvent");
	}
	public static $inst = false;
	public static function init(){
		self::$inst = new self();
	}
}
