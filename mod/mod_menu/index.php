<?php
/* AKM Software.
 * @2015
 * mod_menu Modül Sayfası
 * mod/mod_menu/index.php
 */
class mod_menu_mod extends indexMain
{
    public $mod_menu_model;
    function __construct()
    {
        parent::__construct();
        // Tema Kütüphanesini Çağıralım
        $this->tema = Load::lib('AdminTemplate');
        // Database Kütüphanesini Yükleyelim
        $this->db = Load::lib('Database');
        // Site ayarlarını çekelim
        //$this->site = Kayit::getir('site_config');
        // Dil Dosyasını Çağıralım
        $this->lang = Load::lang('mod_menu');
        // Dil Dosyasını Temaya Gönderelim
        $this->tema->lang($this->lang);
        // Session Kütüphanesi
        $this->session = Load::lib('Session');
        // User Model
        $this->user = Load::model('mod_user.user');
    }
    // Ekleme Sayfası
    public function add()
    {
        // Ekleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_menu', 'add', $this->lang['mod_menu']['role_errors']['add'], 1) === False) {
            return False;
        }
        if (Req::is_Post('add')) {
            $this->_add();
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_menu']['add'];
        $this->tema->tpl('mod_menu/add');
        $this->tema->veri('site', $this->site);
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY seo DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $mod_menu_nested = Load::model('mod_menu');
        $mod_menu_nested->order('order', 'ASC')->all();
        $select_nested_parent_id_result = Helper::nested($mod_menu_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_parent_id_result', $select_nested_parent_id_result);
        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY name DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());
    }

    // Düzenleme Sayfası
    public function edit($id = NULL)
    {
        // Düzenleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_menu', 'edit', $this->lang['mod_menu']['role_errors']['edit'], 1) === False) {
            return False;
        }
        $this->mod_menu_model = Load::model('mod_menu');
        $id = Req::Url_Clear($id);
        if ($this->mod_menu_model->exists_id($id) === False) {
            $this->_error($this->lang['mod_menu']['errors']['record']);
            Run::import('management', '_index');
            $this->tema->tpl('mod_menu/edit');
            return;
        }

        $d = $this->mod_menu_model->getResultOne()->toArray();

        // Eğer Eklenen İçerik Başkasınınsa Yönetme Permissionsi Varmı ?
        if ($this->user->checkPermission('mod_menu', 'moderate_edit', $d['user_id'], $this->lang['mod_menu']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id);
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_menu']['edit'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        $this->tema->tpl('mod_menu/edit');
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY seo DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $mod_menu_nested = Load::model('mod_menu');
        $mod_menu_nested->flush();
        $mod_menu_nested->order('order', 'ASC')->all();
        $select_nested_parent_id_result = Helper::nested($mod_menu_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_parent_id_result', $select_nested_parent_id_result);
        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY name DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());
    }

    // Listeleme Sayfası
    public function list()
    {
        // Listeleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_menu', 'list', $this->lang['mod_menu']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_menu']['list'];
        $this->tema->tpl('mod_menu/list');
        $this->tema->veri('site', $this->site);
        $mod_menu_nested = Load::model('mod_menu');
        $mod_menu_nested->order('order', 'ASC')->all();
        $select_nested_mod_menu_result = Helper::nested($mod_menu_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_mod_menu_result', $select_nested_mod_menu_result);
    }
    public function _order_change()
    {
        $b = Req::Post_Safe('order');
        $mod_menu_model = new mod_menu_model();
        $x = 1;
        foreach ($b as $a) {
            $mod_menu_model->id = $a;
            $mod_menu_model->order = $x;
            if ($mod_menu_model->update('id') === False) {
                //print_r($mod_menu_model,TRUE);
                return FALSE;
            }
            $x++;
        }
        return TRUE;
    }


    // mod_menu Taşıma
    public function tasi()
    {
        // Taşıma Permissionsi varmı?
        if ($this->user->hasPermission('mod_menu', 'edit', $this->lang['mod_menu']['role_errors']['edit']) === False) {
            return False;
        }

        // Standard Support (Matches mod_menu legacy & mod_category logic)
        $mod_menu_model = Load::model('mod_menu');
        $mod_menu_model->id = Req::Post_Safe('id');
        $mod_menu_model->parent_id = Req::Post_Safe('parent_id');
        
        // Update Parent
        if ($mod_menu_model->update('id')) {
            // Update Order
            if ($this->_order_change()) {
                echo 'Oke';
            } else {
                echo 'Error: Order';
            }
        } else {
            echo 'Error: Update';
        }
    }

    // Ekleme Fonksiyonu
    private function _add()
    {
        $this->val = Load::validator('mod_menu.add');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_menu_model = Load::model('mod_menu');

            $this->mod_menu_model->lang = Req::Post_Safe('lang');
            $this->mod_menu_model->name = Req::Post_Safe('name');
            $this->mod_menu_model->url = Req::Post_Safe('url');
            $this->mod_menu_model->file = Req::Post_Safe('file');
            $this->mod_menu_model->parent_id = Req::Post_Safe('parent_id');
            $this->mod_menu_model->target = Req::Post_Safe('target');
            $this->mod_menu_model->status = Req::Post_Safe('status');
            $this->mod_menu_model->html = Req::Post('html');
            $this->mod_menu_model->icon = Req::Post_Safe('icon');
            $this->mod_menu_model->acl_id = Req::Post_Safe('acl_id');
            $this->mod_menu_model->user_id = $this->session->getir('id');
            // Adding order field default value
            $this->mod_menu_model->order = 0;

            if ($this->mod_menu_model->save(TRUE)) {
                $this->_oke_message($this->lang['mod_menu']['message']['oke']['add']);
                Helper::yonlendir($this->site['sadres'] . 'mod_menu/list', 1);
            } else {
                $this->_error_message($this->lang['mod_menu']['message']['error']['add']);
            }
        }
    }

    // Düzenleme Fonksiyonu
    private function _edit($id = NULL)
    {
        $this->val = Load::validator('mod_menu.edit');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_menu_model = Load::model('mod_menu');

            $this->mod_menu_model->id = $id;
            $this->mod_menu_model->lang = Req::Post_Safe('lang');
            $this->mod_menu_model->name = Req::Post_Safe('name');
            $this->mod_menu_model->url = Req::Post_Safe('url');
            $this->mod_menu_model->file = Req::Post_Safe('file');
            $this->mod_menu_model->parent_id = Req::Post_Safe('parent_id');
            $this->mod_menu_model->target = Req::Post_Safe('target');
            $this->mod_menu_model->status = Req::Post_Safe('status');
            $this->mod_menu_model->html = Req::Post('html');
            $this->mod_menu_model->icon = Req::Post_Safe('icon');
            $this->mod_menu_model->acl_id = Req::Post_Safe('acl_id');
            if (isset($this->mod_menu_model->user_id)) {
                unset($this->mod_menu_model->user_id);
            }

            // Eğer Resim Varsa
            $this->mod_menu_model->id = $id;
            if ($this->mod_menu_model->update('id')) {
                $this->_oke_message($this->lang['mod_menu']['message']['oke']['edit']);
                Helper::yonlendir($this->site['sadres'] . 'mod_menu/list', 1);
            } else {
                // Eğer Resim varsa
                $this->_error_message($this->lang['mod_menu']['message']['error']['edit']);
            }
        }
    }



    public function _valid_date($data = '')
    {
        if ($data == '') {
            return false;
        }
        return preg_match('@(\d{4})-(\d{2})-(\d{2})$@', $data);
    }


    // Silme Sayfası
    public function dragdrop_delete()
    {
        // Silme Permissionsi varmı?
        if ($this->user->hasPermission('mod_menu',  'delete', $this->lang['mod_menu']['role_errors'][ 'delete'], 1) === False) {
            return False;
        }
        $sil = Req::Post_Safe( 'delete');
        if ($this->_dragdrop_delete($sil) === TRUE) {
            echo 'Oke';
        } else {
            echo 'ERROR';
            //print_r($this->mod_menu_model);
        }
    }

    // Silme Fonksiyonu
    private function _dragdrop_delete($sil = NULL)
    {
        // mod_menu Modeli Yükleyelim
        $this->mod_menu_model = Load::model('mod_menu');

        $this->mod_menu_model->getid($sil)->getResultOne(TRUE, TRUE);
        if ($this->mod_menu_model->getSize() === 0) {
            print($this->lang['mod_menu']['errors']['record']);
            return FALSE;
        }
        $this->mod_menu_model->sync();

        // Kayıt Başka birine ait ise Permissionsi varmı?
        if ($this->user->checkPermission('mod_menu', 'moderate_delete', $this->mod_menu_model->user_id, $this->lang['mod_menu']['role_errors']['moderate_delete']) === False) {
            return False;
        }
        $new_model = new mod_menu_model();
        if ($new_model->exists_parent_id($sil)) {
            foreach ($new_model->toArray() as $s) {
                if ($this->_dragdrop_delete($s['id']) === TRUE) {
                    continue;
                }
                return FALSE;
            }
        }


        $this->mod_menu_model->getid($sil);
        // Modül varmı ve Gallery Ekleniyormu
        $this->mod = Load::model('mod_gallery_mod');
        if ($this->mod->exists_mod_name("mod_menu") === TRUE) {
            // İçeriğe Ait Gallery Varsa Silelim
            Run::import('mod_gallery', '_remove', ['mod_menu', $this->mod_menu_model->id]);
        }
        // Modül varmı ve Dosya Ekleniyormu
        $this->mod = Load::model('mod_attachment_mod');
        if ($this->mod->exists_mod_name("mod_menu") === TRUE) {
            // İçeriğe Ait Dosya Varsa Silelim
            Run::import('mod_attachment', '_remove', ['mod_menu', $this->mod_menu_model->id]);
        }

        $this->mod_menu_model->delete();
        return TRUE;
    }



    // Valid Mesaj
    private function _oke_message($mesaj)
    {
        $this->tema->veri('oke', True);
        $this->tema->veri('mesaj', $mesaj);
    }

    // Hata Mesaj
    private function _error_message($mesaj)
    {
        $this->tema->veri('hata', True);
        $this->tema->veri('hatalar', [$mesaj]);
    }

    // Error Mesaj
    private function _error($mesaj)
    {
        $this->tema->veri('error', TRUE);
        $this->tema->veri('mesaj', $mesaj);
    }
}
