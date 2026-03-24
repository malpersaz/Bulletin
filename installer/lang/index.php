<?php

class lang extends installerbase implements installerbaseinterface
{
    public $model;
    public $lang;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'lang';
        $this->lang = Load::lang('lang');
    }

    public function install()
    {
        if ($this->check_table_exists('lang') === TRUE) {
            $this->warning($this->lang['lang']['installer']['table_exists']);
        } elseif ($this->install_sql_file('lang.sql')) {
            $this->oke($this->lang['lang']['installer']['sql_added']);
        } else {
            return FALSE;
        }

        $this->add_languages();

        return TRUE;
    }

    /**
     * Seed default languages to the database
     */
    private function add_languages()
    {
        $model = Load::model('lang');
        $languages = [
            ['name' => 'Deutsch', 'seo' => 'de'],
            ['name' => 'English', 'seo' => 'en'],
            ['name' => 'Français', 'seo' => 'fr'],
            ['name' => 'Kurmancî', 'seo' => 'ku'],
            ['name' => 'Soranî', 'seo' => 'ckb'],
            ['name' => 'Svenska', 'seo' => 'sv'],
            ['name' => 'Zazakî', 'seo' => 'zza']
        ];

        foreach ($languages as $lang) {
            $model->where_seo($lang['seo'])->getResult(TRUE, TRUE);
            if ($model->getSize() === 0) {
                $model->name = $lang['name'];
                $model->seo = $lang['seo'];
                if ($model->save()) {
                    $this->oke(sprintf($this->lang['lang']['installer']['lang_added'], $lang['name']));
                }
            } else {
                $this->warning(sprintf($this->lang['lang']['installer']['lang_exists'], $lang['name']));
            }
        }
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
