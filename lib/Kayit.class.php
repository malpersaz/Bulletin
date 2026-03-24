<?php
/*
 * AKM Software İçerik Yönetim Sistemi
 * Web: www.akmsoftware.co.uk
 * E-Mail: info@akmsoftware.co.uk
 * Kullanıcı: a.k.mutlu
 * Zaman: 09.04.2011 - 14:27
 * Kayıt Sınıfı
 * Kayit.class.php
 */
class Kayit {
	static private $data = [];

	static public function getir($key) {
		return (isset(self::$data[$key]) ? self::$data[$key]  : NULL);
	}

	static public function ekle($key, $value) {
		self::$data[$key] = $value;
	}

	static public function add($key, $value) {
		self::ekle($key, $value);
	}

	static public function varmi($key) {
    	return isset(self::$data[$key]);
  	}
	static public function sil($key){
		unset(self::$data[$key]);
	}
	static public function yenile($key,$value){
		if(self::varmi($key)){
			self::sil($key);
		}
		self::ekle($key,$value);
	}
  	static public function hepsi(){
  		require_once __DIR__ . '/dBug.php';
  		new dBug(self::$data);
  	}
}