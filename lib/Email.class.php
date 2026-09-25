<?php
if (defined('root_dir') === false) {
    exit(':)');
}
require_once(lib_dir . 'swift/swift_required.php');

class Email
{
    public $email;
    public $m;
    public $swift;
    public $message;
    public $to;
    public $from;
    public $subject;
    public $html = false;

    public function __construct()
    {
        Load::config('email');
        $this->email = Registry::get('email_config');
        $this->from = [$this->email['smtp']['mail'] => $this->email['smtp']['name'] ?? ''];
        if ($this->email['smtp']['host'] != '' && $this->email['smtp']['user'] != '' && $this->email['smtp']['pass'] != '') {
            $transport = Swift_SmtpTransport::newInstance($this->email['smtp']['host'], $this->email['smtp']['port'])->setUsername($this->email['smtp']['user'])->setPassword($this->email['smtp']['pass']);
            $this->swift = Swift_Mailer::newInstance($transport);
        } else {
            $transport = Swift_MailTransport::newInstance();
            $this->swift = Swift_Mailer::newInstance($transport);
        }
    }

    public function send()
    {
        $this->m = Swift_Message::newInstance();
        $this->m->setSubject($this->subject);
        $this->m->setFrom($this->from);
        $this->m->setTo($this->to);
        $this->m->setBody($this->message);
        if ($this->html == true) {
            $this->m->setContentType("text/html");
        } else {
            $this->m->setContentType("text/plain");
        }
        if ($this->swift->send($this->m)) {
            return true;
        }
        return false;
    }
}
