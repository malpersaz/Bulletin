<?php
require_once __DIR__ . '/TwigLibs.class.php';

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Extension\DebugExtension;

class TwigTemplate
{
    public $dir;
    public $out = [];
    public $dil;
    public $assing_data = [];

    public $twig;
    public $twigLibs;
    public $ctx = NULL;

    public function __construct()
    {
        // Set up Twig environment
        $loader = new FilesystemLoader([
            template_dir . Kayit::getir('admin_template') . DS,
            template_dir . Kayit::getir('app_template') . DS,
            template_dir // Add templates root for macros
        ]);
        $this->twig = new Environment($loader, [
            //'strict_variables' => true,
            'debug' => DEBUG,
            'cache' => false, // Disable cache for development
            //'cache' => root_dir . 'templates_c' . DS,
            'auto_reload' => false,
        ]);

        // $this->twig->addGlobal('site', Kayit::getir('site_config'));
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

    public function getVeri($a)
    {
        return isset($this->twig->getGlobals()[$a]) ? $this->twig->getGlobals()[$a] : NULL;
        // return isset($this->assing_data[$a]) ?  $this->assing_data[$a] : NULL;
    }


    // single key value data
    public function ctx_set($a, $b)
    {
        $this->twigLibs->ctx_set($a, $b);
    }

    // array datas
    public function ctx_add($a, $b)
    {
        $this->twigLibs->ctx_add($a, $b);
    }


    public function veri($a, $b)
    {
        // $this->twigLibs->ctx_add($a, $b);
        $this->twig->addGlobal($a, $b);
        // $this->assing_data[$a] = $b;
    }

    public function setGlobal($key, $value)
    {
        $this->twig->addGlobal($key, $value);
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
                echo $this->twig->render($template . '.twig', $this->assing_data);
            }
        }
    }

    public function lang($a)
    {
        if (is_array($a)) {
            $this->dil = is_array($this->dil) && count($this->dil) > 0 ? array_merge($this->dil, $a) : $a;
        }
        $this->twig->addGlobal('lang', $this->dil);
        // $this->assing_data['lang'] = $this->dil;
    }

    public function fetch_tpl($tpl)
    {
        return $this->twig->render($tpl . '.twig');
        // return $this->twig->render($tpl . '.twig', $this->assing_data);
    }
}
