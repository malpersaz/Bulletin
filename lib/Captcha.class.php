<?php
class Captcha
{
	public $session;
	public $site;
	public $digit;
	public $image;
	public $file;
	public function __construct()
	{
		$this->session = Load::lib('Session');
		$this->site = Load::config('site');
	}

	public function generate()
	{
		$this->create_image();
		$this->session->set('captcha', $this->digit);
        $dir = root_dir . 'cache' . DS . 'captcha' . DS;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
		$this->file = $dir . md5($this->digit) . '.png';
		imagepng($this->image, $this->file);
	}

	public function image()
	{
		if ($this->session->has('captcha')) {
			$this->file = root_dir . 'cache' . DS . 'captcha' . DS . md5($this->session->get('captcha')) . '.png';
			if (file_exists($this->file) === False) {
				$this->generate();
			}
		} else {
			$this->generate();
		}

		if (preg_match('/(?i)msie [1-7]/', $_SERVER['HTTP_USER_AGENT'])) {
			return $this->site['adres'] . "captcha.php?" . base64_encode(file_get_contents($this->file));
		}
        return "data:image/png;base64," . base64_encode(file_get_contents($this->file));
		//return "data:image/png;base64,".base64_encode ( file_get_contents ( $this->file ) );
		//return 'http://192.168.2.2/makina/captcha.php?str='.base64_encode ( file_get_contents ( $this->file ) );
	}

	private function create_image()
	{
		$image = imagecreate(120, 30);

		$white = imagecolorallocate($image, 0xFF, 0xFF, 0xFF);
		$gray = imagecolorallocate($image, 0xC0, 0xC0, 0xC0);
		$darkgray = imagecolorallocate($image, 0x50, 0x50, 0x50);

		srand((float) microtime() * 1000000);

		for ($i = 0; $i < 30; $i++) {
			$x1 = rand(0, 120);
			$y1 = rand(0, 30);
			$x2 = rand(0, 120);
			$y2 = rand(0, 30);
			imageline($image, $x1, $y1, $x2, $y2, $gray);
		}

		for ($i = 0; $i < 5; $i++) {
			$cnum[$i] = rand(0, 9);
		}
		$x = 1;
		for ($i = 0; $i < 5; $i++) {
			$fnt = rand(3, 5);
			$x += rand(12, 20);
			$y = rand(7, 12);
			imagestring($image, $fnt, $x, $y, $cnum[$i], $darkgray);
		}
		$this->image = $image;
		$this->digit = "$cnum[0]$cnum[1]$cnum[2]$cnum[3]$cnum[4]";
	}

	public function check($val)
	{
		if ($this->session->has('captcha') === False) {
			return True;
		}

		if ($this->session->get('captcha') != $val) {
			return True;
		}
		@unlink($this->file = root_dir . 'cache' . DS . 'captcha' . DS . md5($this->session->get('captcha')) . '.png');
		$this->session->remove('captcha');
		return False;
	}
}
