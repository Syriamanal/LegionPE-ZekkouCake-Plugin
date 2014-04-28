<?php

namespace pemapmodder\legionpe\mgs;

use pocketmine\Player;

interface MgMain{
	public function onJoinMg(Player $player);
	public function onQuitMg(Player $player);
	public function getName();
	public function getSessionId();
	/**
	 * @return pocketmine\level\Position
	 */
	public function getSpawn(Player $player, $TID);
	/**
	 * @return string
	 */
	public function getDefaultChatChannel(Player $player, $TID);
	/**
	 * @return bool
	 */
	public function isJoinable();
}
