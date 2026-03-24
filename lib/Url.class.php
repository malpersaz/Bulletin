<?php
class Url {
	public $path, $link, $segments, $host, $url;
	function __construct() {
		$this->link = Req::Url_Clear ( $_SERVER ['REQUEST_URI'] );
		$this->link = preg_replace ( "|" . sub_dir . "(.*)|", "\\1", str_replace ( "//", "/", $this->link ) );
		$this->path = explode ( '/', $this->link );
		$this->host = isset ( $_SERVER ['HTTP_HOST'] ) ? "http://" . Req::Url_Clear ( $_SERVER ['HTTP_HOST'] ) : "https://" . Req::Url_Clear ( $_SERVER ['HTTPS_HOST'] );
		//$this->link =preg_replace ( '%&amp;%', '&', $this->link );
		$this->url = $this->host .sub_dir.$this->link;
		
	}
	public function adres() {
		return $this->path;
	}
	public function root() {
		return $this->link == '';
	}
	public function parca_varmi($gel) {
		return isset ( $this->path [$gel] );
	}
	public function parca($gel) {
		return $this->parca_varmi ( $gel ) ? $this->path [$gel] : False;
	}
	
	public function parse($a) {
		$desenler =  ['%(.*)\?(.*)%', '%&amp;%' ];
		$degis =  ['$2', '&' ];
		// Fix for PHP 8+ compatibility: ensure $a is not null
		$a = $a ?? '';
		parse_str ( preg_replace ( $desenler, $degis, $a ), $sonuc );
		$this->segments = $sonuc;
		return;
	}
	
	public function clear_url() {
		if (is_array ( $this->segments )) {
			foreach ( $this->segments as $key => $val ) {
				if ($val == '') {
					$this->url = str_replace ( "&amp;" . $key . "=", '', $this->url );
					unset ( $this->segments [$key] );
				}
			}
		}
	
	}
	public function get($key) {
		return $this->segments [$key];
	}
	public function get_clear($key) {
		return Req::Url_Clear ( $this->segments [$key] );
	}
	
	public function is_get($key) {
		return $this->segments [$key] != '';
	}
	
	public function exists_get($key) {
		return isset ( $this->segments [$key] );
	}
}
