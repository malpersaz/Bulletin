<?php
require_once __DIR__ . '/TwigLibs.class.php';

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Extension\DebugExtension;

class TwigTemplate
{
    public $dir;
    public $out = [];
    public $lang;
    public $assign_data = [];

    public $twig;
    public $twigLibs;
    public $ctx = NULL;

    public function __construct()
    {
        // Set up Twig environment
        $loader = new FilesystemLoader([
            template_dir . Registry::get('admin_template') . DS,
            template_dir . Registry::get('app_template') . DS,
            template_dir . 'amida' . DS,
            template_dir // Add templates root for macros
        ]);
        $this->twig = new Environment($loader, [
            //'strict_variables' => true,
            'debug' => DEBUG,
            'cache' => false, // Disable cache for development
            //'cache' => root_dir . 'templates_c' . DS,
            'auto_reload' => false,
        ]);

        // $this->twig->addGlobal('site', Registry::get('site_config'));
        $this->twig->addGlobal('twigLibsVersion', '1.0.0');

        // Add debug extension if in debug mode
        if (DEBUG) {
            $this->twig->addExtension(new DebugExtension());
        }

        $this->ctx = new TemplateContext();
        // Load Twig extensions from TwigLibs
        $this->twigLibs = new TwigLibs($this->ctx);

        $this->twig->addExtension($this->twigLibs);

        // Add remaining globals that depend on extension functions
        $this->twig->addGlobal('twigLibsFunctions', array_keys($this->twigLibs->getFunctions()));
        $this->twig->addGlobal('twigLibsFilters', array_keys($this->twigLibs->getFilters()));
    }

    public function get($a)
    {
        return isset($this->twig->getGlobals()[$a]) ? $this->twig->getGlobals()[$a] : NULL;
    }


    // single key value data
    public function ctx_set($a, $b)
    {
        if ($this->twigLibs === null) {
            $this->ctx = $this->ctx ?? new TemplateContext();
            $this->twigLibs = new TwigLibs($this->ctx);
        }
        $this->twigLibs->ctx_set($a, $b);
    }

    // array datas
    public function ctx_add($a, $b)
    {
        if ($this->twigLibs === null) {
            $this->ctx = $this->ctx ?? new TemplateContext();
            $this->twigLibs = new TwigLibs($this->ctx);
        }
        $this->twigLibs->ctx_add($a, $b);
    }


    public function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }
            return;
        }
        $this->assign_data[$key] = $value;
        try {
            $this->twig->addGlobal($key, $value);
        } catch (\LogicException $e) {
            // Runtime already initialized, ignore
        }
        $this->ctx_set($key, $value);
    }

    /**
     * Backward-compatibility forwarding for legacy 'veri' calls.
     * Maps to canonical set().
     *
     * @param string|array $key
     * @param mixed $value
     * @return void
     */
    public function veri($key, $value = null)
    {
        return $this->set($key, $value);
    }

    /**
     * Alias for set() to support Smarty/legacy callers.
     *
     * @param string|array $key
     * @param mixed $value
     * @return void
     */
    public function assign($key, $value = null)
    {
        return $this->set($key, $value);
    }

    /**
     * Magic call fallback to handle legacy method aliases safely.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if ($name === 'veri' || $name === 'assign') {
            return $this->set(...$arguments);
        }
        throw new \BadMethodCallException("Call to undefined method " . __CLASS__ . "::{$name}()");
    }

    public function setGlobal($key, $value)
    {
        try {
            $this->twig->addGlobal($key, $value);
        } catch (\LogicException $e) {
            // Runtime already initialized, ignore
        }
    }
    public function getGlobal($key)
    {
        return $this->twig->getGlobals()[$key];
    }

    public function tpl($a)
    {
        return $this->out[] = $a;
    }

    public function output()
    {
        if (is_array($this->out)) {
            foreach ($this->out as $template) {
                //var_dump($template);
                echo $this->twig->render($template . '.twig', $this->assign_data);
            }
        }
    }

    public function lang($a)
    {
        if (is_array($a)) {
            $this->lang = is_array($this->lang) && count($this->lang) > 0 ? array_merge($this->lang, $a) : $a;
        }
        $this->assign_data['lang'] = $this->lang;
        try {
            $this->twig->addGlobal('lang', $this->lang);
        } catch (\LogicException $e) {
            // Runtime already initialized, ignore
        }
    }

    public function fetch_tpl($tpl)
    {
        return $this->twig->render($tpl . '.twig', $this->assign_data);
    }

    public function fetch($tpl)
    {
        return $this->twig->render($tpl . '.twig', $this->assign_data);
    }
}
