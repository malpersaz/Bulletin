<?php
/*
 * Amed Framework
 * Web: www.akmsoftware.co.uk
 * E-Mail: info@akmsoftware.co.uk
 * @ 2010
 * Session Class
 * Session.class.php
 */
class Session
{
    public $sid;
    public $ip;
    public $agent;
    public $sessionName;

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
        ini_set('session.gc_maxlifetime', Registry::get('session_life_time') * 60);


        if (Registry::has('session_sid')) {
            session_id(Registry::get('session_sid'));
            $this->sid = Registry::get('session_sid');
        }

        if (Registry::has('session_name')) {
            session_name(Registry::get('session_name'));
            $this->sessionName = Registry::get('session_name');
        }
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (!$this->sid) {
            $this->sid = session_id();
        }
        if (!$this->sessionName) {
            $this->sessionName = session_name();
        }

        // Enforce the configured inactivity lifetime (session_life_time, in minutes).
        // Liveness pings (mod_user/session_check) are passive: they exist to DETECT
        // expiry on the client side, so they must not reset the inactivity clock.
        $lifetime = (int) Registry::get('session_life_time') * 60;
        $lastActivity = (int) $this->get('last_activity');
        $isLivenessPing = strpos($_SERVER['REQUEST_URI'] ?? '', 'mod_user/session_check') !== false;
        if ($lifetime > 0 && $lastActivity > 0 && (time() - $lastActivity) > $lifetime) {
            $this->clear();
        } elseif ($lifetime > 0 && $isLivenessPing === false) {
            $this->set('last_activity', time());
        }
        if (Registry::get('session_check_ip') == true) {
            if ($this->has('ip') && $this->get('ip') != $this->ip) {
                print('Please log in again: 1!');
                $this->clear();
                exit();
            }
            $this->set('ip', $this->ip);
        }
        if (Registry::get('session_check_agent') == true) {
            $agentHash = hash('sha256', $this->agent);
            if ($this->has('agent') && $this->get('agent') != $agentHash && $this->get('agent') != md5($this->agent)) {
                print('Please log in again: 2!');
                $this->clear();
                exit();
            }
            $this->set('agent', $agentHash);
        }

        if ($this->has('sid') === false) {
            $this->set('sid', $this->sid);
        }
        if ($this->has('session_name') === false) {
            $this->set('session_name', $this->sessionName);
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

    // Return all session values.
    public function all()
    {
        return isset($_SESSION) && is_array($_SESSION) ? $_SESSION : [];
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
