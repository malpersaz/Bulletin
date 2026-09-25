<?php

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;
use Twig\Environment;

require_once __DIR__ . '/TemplateContext.php';

class TwigLibs extends AbstractExtension
{
    public $ctx;

    public function __construct(TemplateContext $ctx)
    {
        $this->ctx = $ctx;
    }

    /**
     * Repeat a string n times
     * 
     * @param string $string The string to repeat
     * @param int $times Number of times to repeat
     * @return string
     */
    public function repeat(string $string, int $times): string
    {
        return str_repeat($string, max(0, $times));
    }
    public function ctx()
    {
        return $this->ctx;
    }

    // for array datas
    public function ctx_add($name, $content)
    {
        if (!$name || $name === '') {
            throw new \InvalidArgumentException("name is required.");
        }
        if ($this->ctx === null) {
            $this->ctx = new TemplateContext();
        }
        $this->ctx->add($name, $content);
    }
    // for single key value data
    public function ctx_set($name, $content)
    {
        if (!$name || $name === '') {
            throw new \InvalidArgumentException("name is required.");
        }

        if ($this->ctx === null) {
            $this->ctx = new TemplateContext();
        }

        $this->ctx->set($name, $content);
    }


    public function ctx_get(string $key)
    {
        if ($this->ctx === null) {
            $this->ctx = new TemplateContext();
        }
        return $this->ctx->get($key);
    }

    public function lang($a)
    {
        if (Registry::has('TwigTemplate-lib')) {
            Registry::get('TwigTemplate-lib')->lang($a);
        }
    }

    public function ctx_export(string $key, $remove = false, $flush = false)
    {
        if (!$key || $key === '') {
            throw new \InvalidArgumentException("key is required.");
        }
        $result = "";
        // Iterate through the stored context items and append them to the output string
        foreach ($this->ctx->get($key) as $n) {
            if (is_array($remove)) {
                $result .= str_replace($remove, '', $n) . "\n";
            } else {
                $result . $n . "\n";
            }
        }
        // Delete the context key from storage if flush option is enabled
        if ($flush) {
            $this->ctx->delete($key);
        }
        return $result;
    }

    public function getFunctions(): array
    {
        return [

            new TwigFunction('ctx_get', [$this, 'ctx_get']),
            new TwigFunction('ctx_add', [$this, 'ctx_add']),
            new TwigFunction('ctx', [$this, 'ctx']),
            new TwigFunction('ctx_export', [$this, 'ctx_export']),

            new TwigFunction('run', [$this, 'runFunction']),
            new TwigFunction('css', [$this, 'cssFunction']),
            new TwigFunction('cssoptimize', [$this, 'cssOptimizeFunction'], ['needs_environment' => true]),
            new TwigFunction('ie', [$this, 'ieFunction']),
            new TwigFunction('ismobile', [$this, 'isMobileFunction']),
            new TwigFunction('js', [$this, 'jsFunction']),
            new TwigFunction('jsoptimize', [$this, 'jsOptimizeFunction'], ['needs_environment' => true]),
            new TwigFunction('menu', [$this, 'menuFunction'], ['is_variadic' => true]),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('repeat', [$this, 'repeat']),
            new TwigFilter('permission', [$this, 'permissionFilter']),
            new TwigFilter('format_date', [$this, 'formatDateFilter']),
            new TwigFilter('sefurl', [$this, 'sefurlFilter']),
            new TwigFilter('legacy_seo', [$this, 'legacySeoFilter']),
            new TwigFilter('base64_encode', [$this, 'base64Encode']),
            new TwigFilter('time_ago', [$this, 'timeAgoFilter']),
        ];
    }

    public function timeAgoFilter($datetime): string
    {
        return Helper::format_relative_time($datetime);
    }

    public function formatDateFilter(int $timestamp): string
    {
        return format_custom_date($timestamp);
    }

    public function base64Encode($string)
    {
        return base64_encode((string) $string);
    }


    public function runFunction(array $params): ?string
    {

        //var_dump($params);
        if (!isset($params['mod'])) {
            throw new \InvalidArgumentException("url: mod parameter cannot be empty in run command.");
        }

        if (empty($params['method'])) {
            throw new \InvalidArgumentException("url: method parameter cannot be empty in run command.");
        }

        $args = [$this];
        if (isset($params['extra'])) {
            $args = array_merge($args, (array)$params['extra']);
        }

        try {
            return Run::import($params['mod'], $params['method'], $args);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                "url: run returns an error. mod: " . $params['mod'] . 
                ", method: " . $params['method'] . 
                ", error: " . $e->getMessage() . 
                " in " . $e->getFile() . " on line " . $e->getLine()
            );
            return null;
        }
    }

    public function permissionFilter($acl): bool
    {
        if ($acl === '' || $acl == false) {
            return true;
        }

        if (!is_array($acl)) {
            throw new \Exception(
                'Provided $acl variable is not an array: ' . htmlspecialchars(is_scalar($acl) ? (string)$acl : gettype($acl))
            );
        }

        $group_id = $_SESSION['group_id'] ?? 0;
        return in_array($group_id, $acl);
    }

    public function cssFunction(array $params): void
    {
        if (!isset($params['url'])) {
            throw new \InvalidArgumentException("URL parameter is required");
        }

        if (empty($params['url'])) {
            return;
        }


        $this->ctx->add('_cssoptimize', $params['url']);
    }

    public function cssOptimizeFunction(Environment $env, array $params): void
    {
        $site = $env->getGlobals()['site'];
        $_cssoptimize = $this->ctx->get('_cssoptimize') ?? [];

        $this->ctx->delete('_cssoptimize');

        $version = isset($params['v']) ? intval($params['v']) : 1;

        if (is_array($_cssoptimize)) {
            $_cssoptimize = array_unique($_cssoptimize);

            if (isset($params['debug']) && $params['debug'] === true) {
                foreach ($_cssoptimize as $css) {
                    if (preg_match('@(jsdelivr|cloudflare)@', $css)) {
                        echo '<link rel="stylesheet" type="text/css" href="' . $css . '?v=' . $version . '">';
                        echo "\n";
                    } else {
                        echo '<link rel="stylesheet" type="text/css" href="' . $site['taddress'] . $css . '?v=' . $version . '">';
                        echo "\n";
                    }
                }
                return;
            }

            $css = implode('|', $_cssoptimize);
            $css_cache_dir = 'css';
            $css_cache_file = md5($css) . '-v-' . $version . '.css';
            $cachefile = cache_dir . $css_cache_dir . DS . $css_cache_file;

            if (!file_exists($cachefile)) {
                $cssoptimizer = Load::lib('CssOptimizer');
                $cssoptimizer->cachefile = $cachefile;
                $cssoptimizer->css_cache_file = $css_cache_file;
                $cssoptimizer->run($css);
            }

            if (isset($params['tip']) && $params['tip'] == 'inlined') {
                echo '<style type="text/css">';
                echo '/* CSS FILES: ' . $css . ' */';
                $pattern = '/(?i)msie [1-7]/';
                $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                if (strpos($agent, 'MSIE') && preg_match($pattern, $agent)) {
                    echo file_get_contents($cachefile);
                } else {
                    echo file_get_contents($cachefile . "-data");
                }
                echo '</style>';
            } else {
                echo '<link rel="stylesheet" type="text/css" href="' . $site['address'] . 'cache/css/' . $css_cache_file . '?v=' . $version . '">';
            }
        }
    }

    public function ieFunction(array $params, $context): void
    {
        if (isset($params['less_than']) || isset($params['lt'])) {
            $pattern = '/(?i)msie [1-' . $params['version'] . ']/';
        } else {
            $pattern = '/(?i)msie [' . $params['version'] . ']/';
        }

        $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $context['ie'] = (bool) preg_match($pattern, $agent);
    }


    public function isMobileFunction(array $params, $context): bool
    {
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (preg_match('@(android|iphone|ipad)@i', $agent)) {
            $context['mobile'] = true;
            return true;
        }

        $context['mobile'] = false;
        return false;
    }

    public function jsFunction(array $params): void
    {
        if (!isset($params['url'])) {
            throw new \InvalidArgumentException("url: Expected js url parameter.");
        }

        if (empty($params['url'])) {
            return;
        }

        $this->ctx->add('_jsoptimize', $params['url']);
    }

    public function jsOptimizeFunction(Environment $env, array $params): void
    {


        $site = $env->getGlobals()['site'];
        $_jsoptimize = $this->ctx->get('_jsoptimize') ?? [];

        $this->ctx->delete('_jsoptimize');


        $version = isset($params['v']) ? intval($params['v']) : 1;

        if (is_array($_jsoptimize)) {
            $_jsoptimize = array_unique($_jsoptimize);

            if (isset($params['debug']) && $params['debug'] === true) {
                foreach ($_jsoptimize as $js) {
                    if (preg_match('@(jsdelivr|cloudflare)@', $js)) {
                        echo '<script src="' . $js . '?v=' . $version . '" type="text/javascript"></script>';
                        echo "\n";
                    } else {
                        echo '<script src="' . $site['taddress'] . $js . '?v=' . $version . '" type="text/javascript"></script>';
                        echo "\n";
                    }
                }
                return;
            }

            $js = implode('|', $_jsoptimize);
            $js_cache_dir = 'js';
            $js_cache_file = md5($js) . '-v-' . $version . '.js';
            $cachefile = cache_dir . $js_cache_dir . DS . $js_cache_file;

            if (!file_exists($cachefile)) {
                $jsoptimizer = Load::lib('JsOptimizer');
                $jsoptimizer->cachefile = $cachefile;
                $jsoptimizer->js_cache_file = $js_cache_file;
                $jsoptimizer->run($js);
            }

            if (isset($params['tip']) && $params['tip'] == 'inlined') {
                echo '<script type="text/javascript">';
                echo '//<![CDATA[';
                echo "// $js";
                echo file_get_contents($cachefile);
                echo '//]]>';
                echo '</script>';
            } else {
                echo '<script src="' . $site['address'] . 'cache/js/' . $js_cache_file . '?v=' . $version . '" type="text/javascript"></script>';
            }
        }
    }


    public function menuFunction(int $parentId = 0, array $params = []): string
    {
        // Handle both named parameters and direct parameter passing
        $parentId = isset($params['parent_id']) ? intval($params['parent_id']) : intval($parentId);
        
        $menuHelper = Load::helper('index.mod_menu');
        $menuItems = $menuHelper->menu();
        
        return $this->renderMenu($menuItems, $parentId);
    }
    
    private function renderMenu(array $items, int $parentId = 0): string
    {
        $html = '';
        foreach ($items as $item) {
            $itemParentId = $item['parent_id'] ?? 0;
            if ($itemParentId == $parentId) {
                $activeClass = '';
                if (isset($_SERVER['REQUEST_URI']) && isset($item['link']) && strpos($_SERVER['REQUEST_URI'], $item['link']) !== false) {
                    $activeClass = ' active';
                }
                
                $title = $item['name'] ?? ($item['title'] ?? '');
                $html .= '<li class="nav-item' . $activeClass . '">';
                $html .= '<a class="nav-link" href="' . ($item['link'] ?? '#') . '">' . $title . '</a>';
                
                $childrenHtml = $this->renderMenu($items, $item['id']);
                if ($childrenHtml !== '' && $childrenHtml !== '0') {
                    $html .= '<ul class="dropdown-menu">' . $childrenHtml . '</ul>';
                }
                
                $html .= '</li>';
            }
        }
        return $html;
    }

    public function sefurlFilter(?string $s): string
    {
        if ($s === null) {
            throw new \InvalidArgumentException('sefurl modifier error: parameter cannot be null');
        }

        $turkishChars = ['ş', 'Ş', 'ı', 'İ', 'ğ', 'Ğ', 'ü', 'Ü', 'ö', 'Ö', 'Ç', 'ç'];
        $asciiChars   = ['s', 'S', 'i', 'I', 'g', 'G', 'u', 'U', 'o', 'O', 'C', 'c'];
        $s = str_replace($turkishChars, $asciiChars, $s);

        $s = preg_replace('/[^0-9A-Za-z]/', ' ', $s);
        $s = trim($s);
        $s = str_replace(' ', '-', $s);

        for ($i = 0; $i <= 5; $i++) {
            $s = str_replace('--', '-', $s);
        }

        return strtolower($s);
    }

    public function legacySeoFilter(?string $s): string
    {
        if ($s === null) {
            throw new \InvalidArgumentException('legacy_seo modifier error: parameter cannot be null');
        }

        return Helper::legacy_seo($s);
    }
}

function format_custom_date(int $timestamp): string
{
    $days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    return date("d/m/Y", $timestamp) . " " . $days[date("w", $timestamp)] . " " . date("H:i", $timestamp);
}
