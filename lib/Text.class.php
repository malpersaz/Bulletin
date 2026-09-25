<?php
class Text {
	static function title($text) {
		return mb_convert_case($text, MB_CASE_TITLE, "UTF-8");
	}
	
	static function lower($text) {
		return mb_convert_case($text, MB_CASE_LOWER, "UTF-8");
	}
	static function upper($text){
		return mb_convert_case($text, MB_CASE_UPPER, "UTF-8");
	}
}