<?php

/**
 * Cache Manager Helper for Amed Framework
 * Provides safe, authenticated cache clearing operations.
 */
class cache_manager_helper extends indexBase
{
    private $directoryMap = [
        'img' => ['cache/img'],
        'js' => ['cache/js'],
        'video' => ['cache/video', 'cache/video_tmp', 'cache/video_log'],
        'css' => ['cache/css'],
        'sql' => ['cache/sql', 'cache/test_sql'],
        'templates' => ['templates_c'],
        'llms' => ['cache/llms'],
        'captcha' => ['cache/captcha'],
        'market' => ['cache/market'],
        'sports' => ['cache/sports'],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Checks if current request is authorized to clear system caches.
     *
     * @return bool
     */
    private function isAuthorized(): bool
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $isLocal = (
            $host === 'localhost'
            || strpos($host, 'localhost') !== false
            || strpos($host, '127.0.0.1') !== false
            || strpos($host, 'pinggy.link') !== false
            || PHP_SAPI === 'cli'
        );

        if ($isLocal) {
            return true;
        }

        if ($this->session && $this->session->has('id')) {
            $userModel = Load::model('mod_user.user');
            if ($userModel && method_exists($userModel, 'checklogin') && $userModel->checklogin()) {
                $groupId = (int) $this->session->get('group_id');
                if ($groupId === 1 || Role::hasPermission('mod_settings', 'edit') || Role::hasPermission('mod_user', 'acl')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Recursively scans a directory and retrieves all file paths.
     *
     * @param string $dir
     * @return array
     */
    private function scanFiles(string $dir): array
    {
        $allFiles = [];
        $dir = rtrim($dir, DS);
        $absoluteDir = strpos($dir, root_dir) === 0 ? $dir : root_dir . $dir;

        if (!is_dir($absoluteDir)) {
            return $allFiles;
        }

        $handler = @opendir($absoluteDir);
        if ($handler === false) {
            return $allFiles;
        }

        $ignoredNames = ['.', '..', '.gitkeep', '.gitignore', '.htaccess', 'Thumb.db', 'Thumbs.db'];

        while (($file = readdir($handler)) !== false) {
            if (in_array($file, $ignoredNames, true)) {
                continue;
            }

            $path = $absoluteDir . DS . $file;
            if (is_file($path)) {
                $allFiles[] = $path;
            } elseif (is_dir($path)) {
                $allFiles = array_merge($allFiles, $this->scanFiles($path));
            }
        }

        closedir($handler);
        return $allFiles;
    }

    /**
     * Purges all files in the given directory safely.
     *
     * @param string $dir
     * @return array
     */
    private function purgeDirectory(string $dir): array
    {
        $files = $this->scanFiles($dir);
        $deleted = 0;
        $errors = 0;

        foreach ($files as $file) {
            if (@unlink($file)) {
                $deleted++;
            } else {
                $errors++;
            }
        }

        return [
            'directory' => $dir,
            'total' => count($files),
            'deleted' => $deleted,
            'errors' => $errors,
        ];
    }

    /**
     * Resolves target from URL parameter or query string.
     *
     * @param string|null $rawTarget
     * @return string
     */
    private function resolveTarget(?string $rawTarget): string
    {
        $target = $rawTarget !== null ? strtolower(trim((string) $rawTarget)) : '';
        if ($target === '') {
            $target = strtolower(trim((string) ($_GET['cache'] ?? '')));
        }
        return Req::Url_Clear($target);
    }

    /**
     * Main action to render cache management UI or execute purge.
     *
     * @param string|null $targetParam
     * @return void
     */
    public function render(?string $targetParam = null): void
    {
        if (!$this->isAuthorized()) {
            Helper::redirect($this->site['saddress'] . 'login', 0);
            return;
        }

        header('X-Robots-Tag: noindex, nofollow, noarchive');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        $target = $this->resolveTarget($targetParam);
        $results = [];
        $summary = [
            'total' => 0,
            'deleted' => 0,
            'errors' => 0,
            'directories' => 0,
        ];

        if ($target !== '') {
            $dirsToPurge = [];
            if ($target === 'all') {
                foreach ($this->directoryMap as $dirs) {
                    $dirsToPurge = array_merge($dirsToPurge, $dirs);
                }
                $dirsToPurge = array_unique($dirsToPurge);
            } elseif (isset($this->directoryMap[$target])) {
                $dirsToPurge = $this->directoryMap[$target];
            }

            foreach ($dirsToPurge as $dir) {
                $res = $this->purgeDirectory($dir);
                $results[] = $res;
                $summary['total'] += $res['total'];
                $summary['deleted'] += $res['deleted'];
                $summary['errors'] += $res['errors'];
                $summary['directories']++;
            }
        }

        $pageTitle = $this->lang['index']['cache']['title'] ?? 'Cache Management';
        $this->site['title'] = $pageTitle;
        $this->tema->set('site', $this->site);
        $this->tema->set('title', $pageTitle);
        $this->tema->set('cache_target', $target);
        $this->tema->set('cache_results', $results);
        $this->tema->set('cache_summary', $summary);
        $this->tema->tpl('index/cache_clear');
    }
}
