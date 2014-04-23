<?php

namespace pemapmodder\legionpe\mgs\ctf;

class Main{
	public function __construct(){
	}
	public static $ctf = false;
	public static function get(){
		return self::$ctf;
	}
	public static function init(){
		self::$ctf = new self();
	}
}
