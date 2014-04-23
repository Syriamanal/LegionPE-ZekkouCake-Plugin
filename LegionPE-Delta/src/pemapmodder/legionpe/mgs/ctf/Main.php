<?php

namespace pemapmodder\legionpe\mgs\ctf;

use pemapmodder\legionpe\hub\HubPlugin;

use pemapmodder\utils\FileUtils;

use pocketmine\Server;

class Main{
	public function __construct(){
		$this->hub = HubPlugin::get();
		$this->server = Server::getInstance();
	}
	public static $ctf = false;
	public static function get(){
		return self::$ctf;
	}
	public static function init(){
		self::$ctf = new self();
	}
}
