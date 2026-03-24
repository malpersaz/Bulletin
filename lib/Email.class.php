<?php
if (defined ( 'root_dir' ) === False) {
    exit ( ':)' );
}
require_once (lib_dir . 'swift/swift_required.php');

class Email {
	public $email;
    public $m;
    public $swift;
	public $mesaj;
	public $kime;
	public $kimden;
	public $konu;
	public $html = False;
	function __construct() {
		Load::config ( 'email' );
		$this->email = Kayit::getir ( 'email_config' );
		$this->kimden = [$this->email ['smtp'] ['mail']=> $this->email ['smtp'] ['ad'] ];
		if ($this->email ['smtp'] ['host'] != '' && $this->email ['smtp'] ['user'] != '' && $this->email ['smtp'] ['pass'] != '') {
			$transport = Swift_SmtpTransport::newInstance ( $this->email ['smtp'] ['host'], $this->email ['smtp'] ['port'] )->setUsername ( $this->email ['smtp'] ['user'] )->setPassword ( $this->email ['smtp'] ['pass'] );
			$this->swift = Swift_Mailer::newInstance ( $transport );
		} else {
			$transport = Swift_MailTransport::newInstance();
			$this->swift = Swift_Mailer::newInstance ( $transport );
		}
	}

	public function gonder() {
		$this->m = Swift_Message::newInstance ();
		$this->m->setSubject ( $this->konu );
		$this->m->setFrom ($this->kimden );
		$this->m->setTo ( $this->kime );
		$this->m->setBody ( $this->mesaj );
		if ($this->html == True) {
			$this->m->setContentType ( "text/html" );
		} else {
			$this->m->setContentType ( "text/plain" );
		}
		if ($this->swift->send ( $this->m)) {
			return True;
		}
        return False;

	}

}

