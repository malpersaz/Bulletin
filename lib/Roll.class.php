<?php
class Roll {
	static function hasPermission($mod, $permission) {
		if(empty($_SESSION ['permissions'])){
			return False;
		}
		if (isset($_SESSION ['permissions'] [$mod] [$permission]) &&  $_SESSION ['permissions'] [$mod] [$permission] === True) {
			return True;
		}
		return False;
	}
}