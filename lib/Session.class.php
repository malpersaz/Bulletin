<?php
/*
 * Koberz İçerik Yönetim Sistemi
 * @ 2010
 * Session Sınıfı
 * Session.class.php
 */
class Session
{
    public $sid;
    public $ip;
    public $agent;
    public $sad;
    private $basla;

    public function __construct()
    {
        $this->ip = $_SERVER['REMOTE_ADDR'];
        $this->agent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";
        $this->start();
    }

    public function start()
    {
        ini_set('session.gc_probability', 100);
        ini_set('session.gc_divisor', 100);
        ini_set('session.auto_start', 0);
        ini_set('session.gc_maxlifetime', Kayit::getir('session_life_time') * 60);


        if (Kayit::varmi('session_sid')) {
            session_id(Kayit::getir('session_sid'));
            $this->sid = Kayit::getir('session_sid');
        }

        if (Kayit::varmi('session_name')) {
            session_name(Kayit::getir('session_name'));
            $this->sad = Kayit::getir('session_name');
        }
        @session_start();

        if (!$this->sid) {
            $this->sid = session_id();
        }
        if (!$this->sad) {
            $this->sad = session_name();
        }

        if (Kayit::getir('session_check_ip') == true) {
            if ($this->varmi('ip') && $this->getir('ip') != $this->ip) {
                print('Lütfen Tekrar Giriş Yapın: 1!');
                $this->drop();
                exit();
            }
            $this->ac('ip', $this->ip);
        }
        if (Kayit::getir('session_check_agent') == true) {
            if ($this->varmi('agent') && $this->getir('agent') != md5($this->agent)) {
                print('Lütfen Tekrar Giriş Yapın: 2!');
                $this->drop();
                exit();
            }
            $this->ac('agent', md5($this->agent));
        }

        if ($this->varmi('sid') === false) {
            $this->ac('sid', $this->sid);
        }
        if ($this->varmi('sad') === false) {
            $this->ac('sad', $this->sad);
        }
        //var_dump(ini_get('session.auto_start'));
    }

    // Set Session
    public function ac($name, $val)
    {
        return $_SESSION[$name] = $val;
    }

    // Get Session
    public function getir($name)
    {
        return isset($_SESSION[$name]) ? $_SESSION[$name] : false;
    }

    // Varmı?
    public function varmi($name)
    {
        return isset($_SESSION[$name]);
    }

    // Sil
    public function kapat($name)
    {
        unset($_SESSION[$name]);
    }

    // Hepsini Sil
    public function drop()
    {
        foreach ($_SESSION as $key => $ses) {
            $this->kapat($key);
        }
    }

    // Validmi?
    public function valid($name, $val)
    {
        return $this->getir($name) == $val;
    }

    public function dump()
    {
        var_dump($_SESSION);
    }
}

/*
$s = new Session ();
$s->name = 'amidanews';
$s->path = DIRNAME ( __FILE__ ) . '/session/';
$s->sid = '5b6bd037e6bc9874a83f176c901686e6';
$s->check_ip = TRUE;
//$s->check_agent=TRUE;
$s->start ();

$s->dump ();
*/
