<?php

class lang_mod extends indexMain
{
    public function __construct()
    {
        parent::__construct();
        $this->tema = Load::lib('AdminTemplate');
        $this->db = Load::lib('Database');
        $this->session = Load::lib('Session');
        $this->user = Load::model('mod_user.user');
        $this->site = Kayit::getir('site_config');
        $this->lang = Load::lang('lang');
        $this->tema->lang($this->lang);
    }

    public function panel()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['lang']['admin']['permission_denied'], 1) === false) {
            if ($this->isAjaxRequest()) {
                $this->respondWithJson(array(
                    'status' => 'error',
                    'message' => $this->lang['lang']['admin']['permission_denied'],
                ), 403);
            }
            return false;
        }

        if (Req::is_Post('lang_action')) {
            $response = $this->handlePanelAction();
            if ($this->isAjaxRequest()) {
                $this->respondWithJson($response);
            }
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['lang']['admin']['title'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('languages', $this->getLanguages());
        $this->tema->veri('lang_admin_success', $this->pullFlash('lang_admin_success'));
        $this->tema->veri('lang_admin_error', $this->pullFlash('lang_admin_error'));
        $this->tema->tpl('lang/panel');
    }

    private function handlePanelAction()
    {
        $action = Req::Post_Safe('lang_action');
        $languageCode = Req::Url_Clear(Req::Post_Safe('language_code'));
        $language = $this->getLanguageBySeo($languageCode);

        if (empty($language)) {
            return $this->errorResponse($this->lang['lang']['errors']['language_not_found']);
        }

        if ($action === 'activate') {
            $response = $this->activateLanguage($language);
        } elseif ($action === 'deactivate') {
            $response = $this->deactivateLanguage($language);
        } elseif ($action === 'set_default') {
            $response = $this->setDefaultLanguage($language);
        } else {
            $response = $this->errorResponse($this->lang['lang']['errors']['unknown_action']);
        }

        if ($this->isAjaxRequest()) {
            return $response;
        }

        if ($response['status'] === 'success') {
            $this->pushFlash('lang_admin_success', $response['message']);
        } else {
            $this->pushFlash('lang_admin_error', $response['message']);
        }

        Helper::yonlendir($this->site['saddress'] . 'lang/panel', 0);
    }

    private function activateLanguage(array $language)
    {
        if ($this->db->query("UPDATE `lang` SET `status`='y' WHERE `seo`='" . addslashes($language['seo']) . "'") === false) {
            return $this->errorResponse($this->lang['lang']['errors']['update_failed']);
        }

        $this->refreshLanguageStateCache();
        return $this->successResponse(sprintf($this->lang['lang']['messages']['activated'], $language['name']));
    }

    private function deactivateLanguage(array $language)
    {
        if (!empty($language['is_default']) && $language['is_default'] === 'y') {
            return $this->errorResponse($this->lang['lang']['errors']['cannot_deactivate_default']);
        }

        if ($language['seo'] === $this->site['lang']) {
            return $this->errorResponse($this->lang['lang']['errors']['cannot_deactivate_current_interface']);
        }

        if ($this->countActiveLanguages() <= 1) {
            return $this->errorResponse($this->lang['lang']['errors']['cannot_deactivate_last_active']);
        }

        if ($this->db->query("UPDATE `lang` SET `status`='n' WHERE `seo`='" . addslashes($language['seo']) . "'") === false) {
            return $this->errorResponse($this->lang['lang']['errors']['update_failed']);
        }

        $this->refreshLanguageStateCache();
        return $this->successResponse(sprintf($this->lang['lang']['messages']['deactivated'], $language['name']));
    }

    private function setDefaultLanguage(array $language)
    {
        $languageCode = addslashes($language['seo']);

        if ($this->db->query("UPDATE `lang` SET `is_default`='n'") === false) {
            return $this->errorResponse($this->lang['lang']['errors']['update_failed']);
        }

        if ($this->db->query("UPDATE `lang` SET `status`='y', `is_default`='y' WHERE `seo`='" . $languageCode . "'") === false) {
            return $this->errorResponse($this->lang['lang']['errors']['update_failed']);
        }

        $this->refreshLanguageStateCache($language['seo']);
        return $this->successResponse(sprintf($this->lang['lang']['messages']['default_changed'], $language['name']));
    }

    private function getLanguages()
    {
        $this->db->fetch_array("SELECT `name`, `seo`, `status`, `is_default` FROM `lang` ORDER BY `is_default` DESC, `status` DESC, `name` ASC");
        return $this->db->getSize() > 0 ? $this->db->getResult()->toArray() : array();
    }

    private function getLanguageBySeo($languageCode)
    {
        if ($languageCode === '') {
            return array();
        }

        $model = Load::model('lang');
        $model->where_seo($languageCode)->gRo();
        if ($model->getSize() === 0) {
            return array();
        }

        $model->sync();
        return $model->toArray();
    }

    private function countActiveLanguages()
    {
        $this->db->fetch_array("SELECT COUNT(*) AS total FROM `lang` WHERE `status`='y'");
        if ($this->db->getSize() === 0) {
            return 0;
        }

        $result = $this->db->getResultOne()->toArray();
        return isset($result['total']) ? (int) $result['total'] : 0;
    }

    private function refreshLanguageStateCache($forcedDefaultLanguage = null)
    {
        $languages = $this->getLanguages();
        $activeCodes = array();
        $availableLanguages = array();
        $defaultLanguage = $forcedDefaultLanguage !== null ? $forcedDefaultLanguage : (isset($this->site['default_lang']) ? $this->site['default_lang'] : $this->site['lang']);

        foreach ($languages as $language) {
            if (!isset($language['status']) || $language['status'] === 'y') {
                $activeCodes[] = $language['seo'];
                $availableLanguages[] = array(
                    'code' => $language['seo'],
                    'name' => $language['name'],
                );
            }

            if (isset($language['is_default']) && $language['is_default'] === 'y') {
                $defaultLanguage = $language['seo'];
            }
        }

        if (empty($activeCodes)) {
            $activeCodes = array($defaultLanguage);
        }

        $this->site['default_lang'] = $defaultLanguage;
        $this->site['available_languages'] = $availableLanguages;

        Kayit::yenile('active_langs', array_values(array_unique($activeCodes)));
        Kayit::yenile('default_lang', $defaultLanguage);
        Kayit::yenile('lang', isset($this->site['lang']) ? $this->site['lang'] : $defaultLanguage);

        $siteConfig = Kayit::getir('site_config');
        if (is_array($siteConfig)) {
            $siteConfig['default_lang'] = $defaultLanguage;
            $siteConfig['available_languages'] = $availableLanguages;
            Kayit::yenile('site_config', $siteConfig);
        }
    }

    private function pushFlash($key, $message)
    {
        $this->session->set($key, $message);
    }

    private function pullFlash($key)
    {
        if ($this->session->has($key) === false) {
            return '';
        }

        $message = $this->session->get($key);
        $this->session->remove($key);
        return (string) $message;
    }

    private function isAjaxRequest()
    {
        return Req::is_Post('ajax_request') && Req::Post_Safe('ajax_request') === '1';
    }

    private function successResponse($message)
    {
        return array(
            'status' => 'success',
            'message' => $message,
            'languages' => $this->getLanguages(),
            'current_language' => isset($this->site['lang']) ? $this->site['lang'] : '',
        );
    }

    private function errorResponse($message)
    {
        return array(
            'status' => 'error',
            'message' => $message,
            'languages' => $this->getLanguages(),
            'current_language' => isset($this->site['lang']) ? $this->site['lang'] : '',
        );
    }

    private function respondWithJson(array $payload, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit();
    }
}
