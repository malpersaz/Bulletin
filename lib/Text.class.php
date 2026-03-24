<?php
class Text {
	static function title($ad) {
		return mb_convert_case($ad, MB_CASE_TITLE, "UTF-8");
	}
	
	static function lower($ad) {
		return mb_convert_case($ad, MB_CASE_LOWER, "UTF-8");
	}
	static function upper($ad){
		return mb_convert_case($ad, MB_CASE_UPPER, "UTF-8");
	}
}