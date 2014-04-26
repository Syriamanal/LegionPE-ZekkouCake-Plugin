<?php

namespace pemapmodder\smg;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender as Isr;
use pocketmine\command\PluginCommand as Cmd;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\plugin\PluginBase as PB;

class Main extends PB{
	public function onLoad(){
		@mkdir($this->getServer()->getDatapath()."mod-apps/");
		@mkdir($this->getSetver()->getDatapath()."session-seconds/");
	}
	public function onEnable(){
		$cmd = new Cmd("modapp", $this);
		$cmd->setUsage("/modapp <contact methods> <details>");
		$cmd->setDescription("Submits a moderator application. Please read the brief for applying for mod before using this command.");
		$cmd->register($this->getServer()->getCommandMap());
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	public function onCommand(Isr $issuer, Command $cmd, $label, array $args){
		if(!($issuer instanceof Player)) return false;
		$data = file_exists($path = $this->getServer()->getDatapath()."mod-apps/".strtolower($issuer->getName()).".yml") ? yaml_parse(file_get_contents($path)):array("texts"=>array(), "app-secs"=>array());
		$data["app-secs"][] = $this->updateSession($issuer);
		$data["texts"][] = implode(" ", $args);
		file_put_contents($path, yaml_emit($data));
	}
	/**
	 * @priority HIGH
	 */
	public function onJoin(PlayerJoinEvent $evt){
		$this->sessions[$evt->getPlayer()->CID] = time();
	}
	/**
	 * @priority HIGH
	 */
	public function onQuit(PlayerQuitEvent $evt){
		if(!isset($this->sessions[$evt->getPlayer()->CID])) return;
		$this->updateSessions($evt->getPlayer());
		unset($this->sessions[$evt->getPlayer()->CID]);
	}
	public function updateSession($player){
		if(file_exists($file = $this->getServer()->getDatapath()."session-seconds/".$player->getName().".txt"))
		$secs = (int) file_get_contents($file);
		$secs += time();
		$secs -= $this->sessions[$player->CID];
		$this->sessions[$player->CID] = time();
		file_put_contents($file, "$secs");
		return $secs;
	}
}
