<?php
class Url {
	public $path, $link, $segments, $host, $url;
	function __construct() {
		$requestUri = $_SERVER ['REQUEST_URI'] ?? '';
		$requestPath = parse_url ( $requestUri, PHP_URL_PATH );
		$this->link = Req::Url_Clear ( $requestPath );
		$this->link = preg_replace ( "|" . sub_dir . "(.*)|", "\\1", str_replace ( "//", "/", $this->link ) );
		$this->path = explode ( '/', $this->link );
		$this->host = isset ( $_SERVER ['HTTP_HOST'] ) ? "http://" . Req::Url_Clear ( $_SERVER ['HTTP_HOST'] ) : "https://" . Req::Url_Clear ( $_SERVER ['HTTPS_HOST'] );
		//$this->link =preg_replace ( '%&amp;%', '&', $this->link );
		$this->url = $this->host .sub_dir.$this->link;
		
	}
	public function address() {
		return $this->path;
	}
	public function root() {
		return $this->link == '';
	}
	public function hasSegment($index) {
		return isset ( $this->path [$index] );
	}
	public function segment($index) {
		return $this->hasSegment ( $index ) ? $this->path [$index] : False;
	}
	
	public function parse($a = null) {
		$patterns =  ['%(.*)\?(.*)%', '%&amp;%' ];
		$replacements =  ['$2', '&' ];
		if ($a === null || $a === '') {
			$a = $_SERVER['QUERY_STRING'] ?? '';
		}
		parse_str ( preg_replace ( $patterns, $replacements, $a ), $result );
		if (empty($result) && !empty($_GET)) {
			$result = $_GET;
		}
		$this->segments = $result;
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
