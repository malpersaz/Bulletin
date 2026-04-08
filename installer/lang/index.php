<?php

class lang extends installerbase implements installerbaseinterface
{
    const INSTALLER_STATE_SESSION_KEY = 'installer_mobile_state';

    public $tablolar;
    public $model;
    public $lang;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'lang';
        $this->tablolar = array('lang');
        $this->lang = InstallerLang::load();
    }

    public function install()
    {
        if ($this->install_or_sync_table() === FALSE) {
            return FALSE;
        }

        if ($this->check_table_exists('mods') === TRUE) {
            if ($this->insert_mods() === FALSE) {
                return FALSE;
            }
        }

        if ($this->add_languages() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    private function install_or_sync_table()
    {
        if ($this->check_table_exists('lang') === TRUE) {
            $this->warning($this->lang['installer']['table_exists']);
            return $this->sync_lang_table_schema();
        }

        if ($this->install_sql_file('lang.sql') === FALSE) {
            return FALSE;
        }

        $this->oke($this->lang['installer']['sql_added']);
        return TRUE;
    }

    private function sync_lang_table_schema()
    {
        $columns = array(
            'status' => "ALTER TABLE `lang` ADD COLUMN `status` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'y' AFTER `seo`",
            'is_default' => "ALTER TABLE `lang` ADD COLUMN `is_default` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'n' AFTER `status`",
        );

        foreach ($columns as $column => $sql) {
            if ($this->check_column_exists('lang', $column) === TRUE) {
                continue;
            }

            if ($this->db->query($sql) === FALSE) {
                $this->error("Language table column '" . $column . "' could not be added");
                return FALSE;
            }

            $this->oke("Language table column '" . $column . "' added");
        }

        return TRUE;
    }

    /**
     * Seed default languages to the database
     */
    private function add_languages()
    {
        $defaultLanguage = $this->resolve_default_language();
        $languages = [
            ['name' => 'العربية', 'seo' => 'ar'],
            ['name' => 'Deutsch', 'seo' => 'de'],
            ['name' => 'English', 'seo' => 'en'],
            ['name' => 'فارسی', 'seo' => 'fa'],
            ['name' => 'Français', 'seo' => 'fr'],
            ['name' => 'Kurmancî', 'seo' => 'ku'],
            ['name' => 'Soranî', 'seo' => 'ckb'],
            ['name' => 'Svenska', 'seo' => 'sv'],
            ['name' => 'Türkçe', 'seo' => 'tr'],
            ['name' => 'Zazakî', 'seo' => 'zza']
        ];

        foreach ($languages as $language) {
            $model = Load::model('lang');
            $model->where_seo($language['seo'])->getResult(TRUE, TRUE);

            if ($model->getSize() === 0) {
                $record = Load::model('lang');
                $record->name = $language['name'];
                $record->seo = $language['seo'];
                if ($this->check_column_exists('lang', 'status') === TRUE) {
                    $record->status = 'y';
                }
                if ($this->check_column_exists('lang', 'is_default') === TRUE) {
                    $record->is_default = $language['seo'] === $defaultLanguage ? 'y' : 'n';
                }
                if ($record->save()) {
                    $this->oke(sprintf($this->lang['installer']['lang_added'], $language['name']));
                } else {
                    $this->error("Language '" . $language['seo'] . "' could not be added");
                    return FALSE;
                }
            } else {
                $model->sync();
                $model->name = $language['name'];
                if ($this->check_column_exists('lang', 'status') === TRUE) {
                    $model->status = 'y';
                }
                if ($this->check_column_exists('lang', 'is_default') === TRUE) {
                    $model->is_default = $language['seo'] === $defaultLanguage ? 'y' : 'n';
                }
                if ($model->update('seo') === FALSE) {
                    $this->error("Language '" . $language['seo'] . "' could not be updated");
                    return FALSE;
                }
                $this->warning(sprintf($this->lang['installer']['lang_exists'], $language['name']));
            }
        }

        return $this->normalize_default_language($defaultLanguage);
    }

    private function resolve_default_language()
    {
        $session = Load::lib('Session');
        $state = $session->get(self::INSTALLER_STATE_SESSION_KEY);

        if (is_array($state) && !empty($state['project_default_language'])) {
            return (string) $state['project_default_language'];
        }

        return 'ku';
    }

    private function normalize_default_language($defaultLanguage)
    {
        if ($this->check_column_exists('lang', 'is_default') === FALSE) {
            return TRUE;
        }

        $defaultLanguage = addslashes((string) $defaultLanguage);

        if ($this->db->query("UPDATE `lang` SET `is_default`='n'") === FALSE) {
            $this->error("Language defaults could not be reset");
            return FALSE;
        }

        if ($this->db->query("UPDATE `lang` SET `status`='y', `is_default`='y' WHERE `seo`='" . $defaultLanguage . "'") === FALSE) {
            $this->error("Default language '" . $defaultLanguage . "' could not be set");
            return FALSE;
        }

        $this->db->fetch_array("SELECT `seo` FROM `lang` WHERE `is_default`='y' LIMIT 1");
        if ($this->db->getSize() !== 0) {
            return TRUE;
        }

        $fallbackSql = "UPDATE `lang` SET `status`='y', `is_default`='y' WHERE `seo`='ku'";
        if ($this->db->query($fallbackSql) === FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    public function uninstall()
    {
        $this->remove_tables();
        $this->remove_mods();
        $this->remove_rool();
        $this->remove_perm();
        return TRUE;
    }
}
