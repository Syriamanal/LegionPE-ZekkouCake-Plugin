<?php

namespace pemapmodder\legionpe\hub;

class Hub{
	public static $hub;
	public static function init(){
		self::$hub=new Hub();
	}
	public function __construct(){
		
	}
}
