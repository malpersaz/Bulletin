<?php
/*
 * Koberz Content Management System
 * @ 2010
 * Session Class
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
            if ($this->has('ip') && $this->get('ip') != $this->ip) {
                print('Please log in again: 1!');
                $this->clear();
                exit();
            }
            $this->set('ip', $this->ip);
        }
        if (Kayit::getir('session_check_agent') == true) {
            if ($this->has('agent') && $this->get('agent') != md5($this->agent)) {
                print('Please log in again: 2!');
                $this->clear();
                exit();
            }
            $this->set('agent', md5($this->agent));
        }

        if ($this->has('sid') === false) {
            $this->set('sid', $this->sid);
        }
        if ($this->has('sad') === false) {
            $this->set('sad', $this->sad);
        }
        //var_dump(ini_get('session.auto_start'));
    }

    // Store a session value.
    public function set($name, $val)
    {
        return $_SESSION[$name] = $val;
    }

    // Read a session value.
    public function get($name)
    {
        return isset($_SESSION[$name]) ? $_SESSION[$name] : false;
    }

    // Check whether a session key exists.
    public function has($name)
    {
        return isset($_SESSION[$name]);
    }

    // Remove a single session key.
    public function remove($name)
    {
        unset($_SESSION[$name]);
    }

    // Remove every session value.
    public function clear()
    {
        foreach ($_SESSION as $key => $ses) {
            $this->remove($key);
        }
    }

    // Compare a session value with an expected value.
    public function matches($name, $val)
    {
        return $this->get($name) == $val;
    }

    /**
     * Store a one-time flash message.
     * @param string $type success, error, warning, info
     * @param string $message
     */
    public function setFlash($type, $message)
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    /**
     * Read and clear the one-time flash message.
     * @return array|false
     */
    public function getFlash()
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return false;
    }

    public function dump()
    {
        var_dump($_SESSION);
    }
}
