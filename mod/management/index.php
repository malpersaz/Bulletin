<?php

require_once(__DIR__ . '/../index/indexBase.php');

class management_mod extends indexBase
{
    private $toggleableModules = [
        'mod_content',
        'mod_news',
        'mod_publication',
        'mod_video_embed',
    ];

    private function runtime()
    {
        return Load::lib('ModuleRuntime');
    }

    private function canManageModuleStatus()
    {
        $userLang = Load::lang('mod_user');
        return $this->user->hasPermission('mod_user', 'acl', $userLang['mod_user']['role_errors']['acl'], 1);
    }

    private function moduleStatusColumnExists()
    {
        if ($this->db->getAdapterType() && strpos($this->db->getAdapterType(), 'PDO_SQLITE') !== false) {
            $this->db->fetch_array("PRAGMA table_info('mods')");
            $result = $this->db->toArray();
            if (is_array($result)) {
                foreach ($result as $row) {
                    if (isset($row['name']) && $row['name'] === 'status') {
                        return true;
                    }
                }
            }
            return false;
        }

        $this->db->fetch_array("SHOW COLUMNS FROM `mods` LIKE 'status'");
        return $this->db->getSize() !== 0;
    }

    private function moduleSchemaReadyColumnExists()
    {
        if ($this->db->getAdapterType() && strpos($this->db->getAdapterType(), 'PDO_SQLITE') !== false) {
            $this->db->fetch_array("PRAGMA table_info('mods')");
            $result = $this->db->toArray();
            if (is_array($result)) {
                foreach ($result as $row) {
                    if (isset($row['name']) && $row['name'] === 'schema_ready') {
                        return true;
                    }
                }
            }
            return false;
        }

        $this->db->fetch_array("SHOW COLUMNS FROM `mods` LIKE 'schema_ready'");
        return $this->db->getSize() !== 0;
    }

    private function normalizeModuleStatus($status)
    {
        if ($status === 'enable' || $status === 'ready' || $status === 'installed') {
            return 'ready';
        }

        if ($status === 'disable' || $status === 'disabled') {
            return 'disabled';
        }

        return null;
    }

    private function redirectBackToPanel()
    {
        $target = $this->site['saddress'] . 'mod_user/panel';
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $target = $_SERVER['HTTP_REFERER'];
        }
        Helper::yonlendir($target, 0);
    }

    private function loadRegistryRow($moduleName)
    {
        $model = Load::model('mods');
        $model->where_mod_name($moduleName)->limit(1)->gRo();

        if ($model->getSize() === 0) {
            return null;
        }

        $model->sync();
        return $model;
    }

    private function persistRegistryState($model, $status, $schemaReady)
    {
        $model->status = $status;
        if ($this->moduleSchemaReadyColumnExists()) {
            $model->schema_ready = $schemaReady;
        }
        if (property_exists($model, 'updated_at')) {
            $model->updated_at = NOW;
        }

        return $model->update('mod_id') !== false;
    }

    private function isModuleInstalled($moduleName)
    {
        return $this->runtime()->isModuleInstalled($moduleName);
    }

    protected function isFeatureReady($feature)
    {
        return $this->runtime()->isReady($feature);
    }

    private function tableExists($tableName)
    {
        return $this->runtime()->tableExists($tableName);
    }

    private function getModuleHealth($moduleName)
    {
        return $this->runtime()->getModuleHealth($moduleName);
    }

    function __construct()
    {
        parent::__construct();

        $this->tema = Load::lib('AdminTemplate');
        $this->lang = Load::lang('management');
        $this->tema->lang($this->lang);
        $this->db = Load::lib('Database');
        $this->session = Load::lib('Session');
        $this->user = Load::model('mod_user.user');
    }

    public function _index()
    {
        $this->_site();
        $this->tema->lang(Load::lang('mod_user'));
        $this->tema->lang(Load::lang('mod_user_pm'));
        $this->tema->lang(Load::lang('mod_user_group'));
        $this->tema->lang(Load::lang('mod_acl'));
        $this->tema->lang(Load::lang('mod_seo'));

        $sharedModules = [
            'mod_content' => $this->isFeatureReady('content.front'),
            'mod_news' => $this->isFeatureReady('news.front'),
            'mod_publication' => $this->isFeatureReady('publication.front'),
            'mod_video_embed' => $this->isFeatureReady('video_embed.front'),
            'mod_product' => $this->runtime()->isModuleReady('mod_product'),
            'mod_service' => $this->runtime()->isModuleReady('mod_service'),
            'mod_page' => $this->runtime()->isModuleReady('mod_page'),
        ];
        $sharedModuleHealth = [
            'mod_content' => $this->getModuleHealth('mod_content'),
            'mod_news' => $this->getModuleHealth('mod_news'),
            'mod_publication' => $this->getModuleHealth('mod_publication'),
            'mod_video_embed' => $this->getModuleHealth('mod_video_embed'),
            'mod_category' => $this->getModuleHealth('mod_category'),
            'mod_news_category' => $this->getModuleHealth('mod_news_category'),
            'mod_publication_category' => $this->getModuleHealth('mod_publication_category'),
            'mod_video_embed_category' => $this->getModuleHealth('mod_video_embed_category'),
        ];

        $this->tema->setGlobal('shared_admin_modules', $sharedModules);
        $this->tema->setGlobal('shared_admin_module_health', $sharedModuleHealth);

        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY seo ASC");
        $this->tema->setGlobal('lang_lang', $this->db->getResult()->toArray());

        if (
            $this->isModuleInstalled('mod_video_embed_category')
            && $this->tableExists('mod_video_embed_category')
        ) {
            $mod_video_category = Load::model('mod_video_embed_category');
            $mod_video_category->select('id, name, lang');
            $mod_video_category->addWhere("parent_id != '0'");
            $mod_video_category->all();
            $this->tema->setGlobal('video_embed_categories', $mod_video_category->toArray());
        } else {
            $this->tema->setGlobal('video_embed_categories', []);
        }
    }

    public function _site()
    {
        $this->site['title'] = $this->lang['management']['1'];
        $this->tema->setGlobal('site', $this->site);
    }


    public function _lang()
    {
        $this->db->fetch_array("SELECT name,seo FROM `lang` LIMIT 0,3");
        $this->tema->setGlobal('_lang_list', $this->db->result);
    }

    public function module_status($moduleName = null, $status = null)
    {
        Run::import('management', '_index');

        if ($this->canManageModuleStatus() === false) {
            return false;
        }

        $moduleName = Req::Url_Clear((string) $moduleName);
        $status = $this->normalizeModuleStatus(Req::Url_Clear((string) $status));

        if (!in_array($moduleName, $this->toggleableModules, true) || $status === null) {
            $this->redirectBackToPanel();
            return false;
        }

        if ($this->moduleStatusColumnExists() === false) {
            error_log('module_status_toggle_failed: missing status column for module ' . $moduleName);
            $this->redirectBackToPanel();
            return false;
        }

        $model = $this->loadRegistryRow($moduleName);
        if ($model === null) {
            error_log('module_status_toggle_failed: module row not found for ' . $moduleName);
            $this->redirectBackToPanel();
            return false;
        }

        $schemaReady = ($status === 'ready') ? 'y' : (($status === 'disabled') ? 'n' : 'n');
        if ($this->persistRegistryState($model, $status, $schemaReady) === false) {
            error_log('module_status_toggle_failed: update failed for ' . $moduleName . ' -> ' . $status);
        } else {
            error_log('module_status_toggled: ' . json_encode([
                'module' => $moduleName,
                'status' => $status,
                'user_id' => $this->session->get('id'),
            ]));
        }

        $this->redirectBackToPanel();
        return true;
    }

    public function module_repair($moduleName = null)
    {
        Run::import('management', '_index');

        if ($this->canManageModuleStatus() === false) {
            return false;
        }

        $moduleName = Req::Url_Clear((string) $moduleName);
        if (!in_array($moduleName, $this->toggleableModules, true)) {
            $this->redirectBackToPanel();
            return false;
        }

        if ($this->moduleStatusColumnExists() === false) {
            error_log('module_repair_failed: missing status column for module ' . $moduleName);
            $this->redirectBackToPanel();
            return false;
        }

        $model = $this->loadRegistryRow($moduleName);
        if ($model === null) {
            error_log('module_repair_failed: module row not found for ' . $moduleName);
            $this->redirectBackToPanel();
            return false;
        }

        if ($model->status === 'disabled') {
            error_log('module_repair_skipped: module is disabled ' . $moduleName);
            $this->redirectBackToPanel();
            return false;
        }

        $health = $this->getModuleHealth($moduleName);
        $targetStatus = 'ready';
        $schemaReady = 'y';

        if (!empty($health['missing_modules']) || !empty($health['missing_tables'])) {
            $targetStatus = 'degraded';
            $schemaReady = 'n';
        }

        if ($this->persistRegistryState($model, $targetStatus, $schemaReady) === false) {
            error_log('module_repair_failed: update failed for ' . $moduleName);
        } else {
            error_log('module_repaired: ' . json_encode([
                'module' => $moduleName,
                'status' => $targetStatus,
                'schema_ready' => $schemaReady,
                'user_id' => $this->session->get('id'),
            ]));
        }

        $this->redirectBackToPanel();
        return true;
    }
}
