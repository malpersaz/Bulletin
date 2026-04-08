<?php

class mod_page extends installerbase implements installerbaseinterface
{
    public $tablolar;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_page';
        $this->tablolar = array(
            'mod_page'
        );
        $this->rolls = array(
            'add', 'edit',  'delete', 'list', 'gallery','file', 'moderate_edit', 'moderate_delete'
        );
    }

    public function install()
    {
        // Tabloları Oluşturalım
        if ($this->create_tables() === FALSE) {
            return FALSE;
        }
        // Modülü Ekleyelim
        if ($this->insert_mods() === FALSE) {
            return FALSE;
        }
        // Roller ve Permleri Ekleyelim
        if ($this->insert_roll() === FALSE) {
            return FALSE;
        }
        // Rûpelên nimûneyî lê zêde bikin
        if ($this->addsayfa() === FALSE) {
            return FALSE;
        }
        return TRUE;
    }

    public function addsayfa()
    {
        $veri = array(
            // =============================================
            // Root Kategorî — Her ziman ji bo xwe
            // =============================================

            // 1 - English Root
            array(
                'name' => 'English Pages',
                'lang' => 'en',
                'parent_id' => '0',
                'seo' => 'English_Pages',
                'title' => 'English Pages',
                'keywords' => 'English Pages',
                'description' => 'English Pages',
                'root' => 'y',
                'user_id' => 1
            ),
            // 2 - Kurdish (Kurmancî) Root
            array(
                'name' => 'Rûpelên Kurdî',
                'lang' => 'ku',
                'parent_id' => '0',
                'seo' => 'Rûpelên_Kurdî',
                'title' => 'Rûpelên Kurdî',
                'keywords' => 'Rûpelên Kurdî',
                'description' => 'Rûpelên Kurdî',
                'root' => 'y',
                'user_id' => 1
            ),
            // 3 - Kurdish (Soranî) Root
            array(
                'name' => 'لاپەڕەکانی کوردی',
                'lang' => 'ckb',
                'parent_id' => '0',
                'seo' => 'لاپەڕەکانی_کوردی',
                'title' => 'لاپەڕەکانی کوردی',
                'keywords' => 'لاپەڕەکانی کوردی',
                'description' => 'لاپەڕەکانی کوردی',
                'root' => 'y',
                'user_id' => 1
            ),
            // 4 - Arabic Root
            array(
                'name' => 'الصفحات العربية',
                'lang' => 'ar',
                'parent_id' => '0',
                'seo' => 'الصفحات_العربية',
                'title' => 'الصفحات العربية',
                'keywords' => 'الصفحات العربية',
                'description' => 'الصفحات العربية',
                'root' => 'y',
                'user_id' => 1
            ),
            // 5 - Deutsch Root
            array(
                'name' => 'Deutsche Seiten',
                'lang' => 'de',
                'parent_id' => '0',
                'seo' => 'Deutsche_Seiten',
                'title' => 'Deutsche Seiten',
                'keywords' => 'Deutsche Seiten',
                'description' => 'Deutsche Seiten',
                'root' => 'y',
                'user_id' => 1
            ),
            // 6 - French Root
            array(
                'name' => 'Pages Françaises',
                'lang' => 'fr',
                'parent_id' => '0',
                'seo' => 'Pages_Françaises',
                'title' => 'Pages Françaises',
                'keywords' => 'Pages Françaises',
                'description' => 'Pages Françaises',
                'root' => 'y',
                'user_id' => 1
            ),
            // 7 - Swedish Root
            array(
                'name' => 'Svenska Sidor',
                'lang' => 'sv',
                'parent_id' => '0',
                'seo' => 'Svenska_Sidor',
                'title' => 'Svenska Sidor',
                'keywords' => 'Svenska Sidor',
                'description' => 'Svenska Sidor',
                'root' => 'y',
                'user_id' => 1
            ),
            // 8 - Turkish Root
            array(
                'name' => 'Türkçe Sayfalar',
                'lang' => 'tr',
                'parent_id' => '0',
                'seo' => 'Türkçe_Sayfalar',
                'title' => 'Türkçe Sayfalar',
                'keywords' => 'Türkçe Sayfalar',
                'description' => 'Türkçe Sayfalar',
                'root' => 'y',
                'user_id' => 1
            ),
            // 9 - Zazakî Root
            array(
                'name' => 'Pelê Zazakî',
                'lang' => 'zza',
                'parent_id' => '0',
                'seo' => 'Pelê_Zazakî',
                'title' => 'Pelê Zazakî',
                'keywords' => 'Pelê Zazakî',
                'description' => 'Pelê Zazakî',
                'root' => 'y',
                'user_id' => 1
            ),

            // =============================================
            // Rûpela Têkiliyê (Contact / İletişim)
            // =============================================

            // English — Contact
            array(
                'name' => 'Contact',
                'lang' => 'en',
                'parent_id' => '1',
                'seo' => 'Contact',
                'title' => 'Contact',
                'keywords' => 'Contact',
                'description' => 'Contact',
                'root' => 'n',
                'user_id' => 1
            ),
            // Kurdish (Kurmancî) — Têkilî
            array(
                'name' => 'Têkilî',
                'lang' => 'ku',
                'parent_id' => '2',
                'seo' => 'Têkilî',
                'title' => 'Têkilî',
                'keywords' => 'Têkilî',
                'description' => 'Têkilî',
                'root' => 'n',
                'user_id' => 1
            ),
            // Kurdish (Soranî) — پەیوەندی
            array(
                'name' => 'پەیوەندی',
                'lang' => 'ckb',
                'parent_id' => '3',
                'seo' => 'پەیوەندی',
                'title' => 'پەیوەندی',
                'keywords' => 'پەیوەندی',
                'description' => 'پەیوەندی',
                'root' => 'n',
                'user_id' => 1
            ),
            // Arabic — اتصل بنا
            array(
                'name' => 'اتصل بنا',
                'lang' => 'ar',
                'parent_id' => '4',
                'seo' => 'اتصل_بنا',
                'title' => 'اتصل بنا',
                'keywords' => 'اتصل بنا',
                'description' => 'اتصل بنا',
                'root' => 'n',
                'user_id' => 1
            ),
            // Deutsch — Kontakt
            array(
                'name' => 'Kontakt',
                'lang' => 'de',
                'parent_id' => '5',
                'seo' => 'Kontakt',
                'title' => 'Kontakt',
                'keywords' => 'Kontakt',
                'description' => 'Kontakt',
                'root' => 'n',
                'user_id' => 1
            ),
            // French — Contact
            array(
                'name' => 'Contact',
                'lang' => 'fr',
                'parent_id' => '6',
                'seo' => 'Contact',
                'title' => 'Contact',
                'keywords' => 'Contact',
                'description' => 'Contact',
                'root' => 'n',
                'user_id' => 1
            ),
            // Swedish — Kontakt
            array(
                'name' => 'Kontakt',
                'lang' => 'sv',
                'parent_id' => '7',
                'seo' => 'Kontakt',
                'title' => 'Kontakt',
                'keywords' => 'Kontakt',
                'description' => 'Kontakt',
                'root' => 'n',
                'user_id' => 1
            ),
            // Turkish — İletişim
            array(
                'name' => 'İletişim',
                'lang' => 'tr',
                'parent_id' => '8',
                'seo' => 'İletişim',
                'title' => 'İletişim',
                'keywords' => 'İletişim',
                'description' => 'İletişim',
                'root' => 'n',
                'user_id' => 1
            ),
            // Zazakî — Îrtibat
            array(
                'name' => 'Îrtibat',
                'lang' => 'zza',
                'parent_id' => '9',
                'seo' => 'Îrtibat',
                'title' => 'Îrtibat',
                'keywords' => 'îrtibat',
                'description' => 'Îrtibat',
                'root' => 'n',
                'user_id' => 1
            ),

            // =============================================
            // Rûpela Derbarê (About / Hakkımızda)
            // =============================================

            // English — About Us
            array(
                'name' => 'About Us',
                'lang' => 'en',
                'parent_id' => '1',
                'seo' => 'About_Us',
                'title' => 'About Us',
                'keywords' => 'About Us',
                'description' => 'About Us',
                'root' => 'n',
                'user_id' => 1
            ),
            // Kurdish (Kurmancî) — Derbarê me
            array(
                'name' => 'Derbarê me',
                'lang' => 'ku',
                'parent_id' => '2',
                'seo' => 'Derbarê_me',
                'title' => 'Derbarê me',
                'keywords' => 'Derbarê me',
                'description' => 'Derbarê me',
                'root' => 'n',
                'user_id' => 1
            ),
            // Kurdish (Soranî) — دەربارەی ئێمە
            array(
                'name' => 'دەربارەی ئێمە',
                'lang' => 'ckb',
                'parent_id' => '3',
                'seo' => 'دەربارەی_ئێمە',
                'title' => 'دەربارەی ئێمە',
                'keywords' => 'دەربارەی ئێمە',
                'description' => 'دەربارەی ئێمە',
                'root' => 'n',
                'user_id' => 1
            ),
            // Arabic — من نحن
            array(
                'name' => 'من نحن',
                'lang' => 'ar',
                'parent_id' => '4',
                'seo' => 'من_نحن',
                'title' => 'من نحن',
                'keywords' => 'من نحن',
                'description' => 'من نحن',
                'root' => 'n',
                'user_id' => 1
            ),
            // Deutsch — Über uns
            array(
                'name' => 'Über uns',
                'lang' => 'de',
                'parent_id' => '5',
                'seo' => 'Über_uns',
                'title' => 'Über uns',
                'keywords' => 'Über uns',
                'description' => 'Über uns',
                'root' => 'n',
                'user_id' => 1
            ),
            // French — À propos
            array(
                'name' => 'À propos',
                'lang' => 'fr',
                'parent_id' => '6',
                'seo' => 'À_propos',
                'title' => 'À propos',
                'keywords' => 'À propos',
                'description' => 'À propos',
                'root' => 'n',
                'user_id' => 1
            ),
            // Swedish — Om oss
            array(
                'name' => 'Om oss',
                'lang' => 'sv',
                'parent_id' => '7',
                'seo' => 'Om_oss',
                'title' => 'Om oss',
                'keywords' => 'Om oss',
                'description' => 'Om oss',
                'root' => 'n',
                'user_id' => 1
            ),
            // Turkish — Hakkımızda
            array(
                'name' => 'Hakkımızda',
                'lang' => 'tr',
                'parent_id' => '8',
                'seo' => 'Hakkımızda',
                'title' => 'Hakkımızda',
                'keywords' => 'Hakkımızda',
                'description' => 'Hakkımızda',
                'root' => 'n',
                'user_id' => 1
            ),
            // Zazakî — Ma Heqa
            array(
                'name' => 'Ma Heqa',
                'lang' => 'zza',
                'parent_id' => '9',
                'seo' => 'Ma_Heqa',
                'title' => 'Ma Heqa',
                'keywords' => 'Ma Heqa',
                'description' => 'Ma Heqa',
                'root' => 'n',
                'user_id' => 1
            ),
        );

        $this->model = Load::model('mod_page');
        foreach ($veri AS $key => $value) {
            if ($this->model->exists_name_and_lang($value['name'],$value['lang']) === FALSE) {
                $this->oke($value['name'] . " hat zêdekirin");
                $keys = array_keys($value);
                foreach ($keys AS $k) {
                    $this->model->$k = $value[$k];
                }
                // Default value ji bo fieldên ku di data'yê de tune ne
                if (!isset($value['order'])) {
                    $this->model->order = 0;
                }
                if ($this->model->save() === FALSE) {
                    $this->error($value['name'] . " dema ku dihat zêdekirin çewtî derket" . print_r($this->model, TRUE));
                    return FALSE;
                }
                continue;

            } else {
                $this->warning($value['name'] . " berê hatiye zêdekirin");
            }
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