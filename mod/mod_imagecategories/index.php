<?php
/* AKM Software.
 * @2015
 * mod_imagecategories Modül Sayfası
 * mod/mod_imagecategories/index.php
 */
class mod_imagecategories_mod extends indexMain
{
    public $mod_imagecategories_model;
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
        $this->lang = Load::lang('mod_imagecategories');
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
        if ($this->user->hasPermission('mod_imagecategories', 'add', $this->lang['mod_imagecategories']['role_errors']['add'], 1) === False) {
            return False;
        }
        if (Req::is_Post('add')) {
            $this->_add();
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_imagecategories']['add'];
        $this->tema->tpl('mod_imagecategories/add');
        $this->tema->veri('site', $this->site);
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY seo DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $mod_imagecategories_nested = Load::model('mod_imagecategories');
        $mod_imagecategories_nested->order('order', 'ASC')->all();
        $select_nested_parent_id_result = Helper::nested($mod_imagecategories_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_parent_id_result', $select_nested_parent_id_result);
        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY id DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());
        $mod_imagecategories_nested = Load::model('mod_menu');
        $mod_imagecategories_nested->order('order', 'ASC')->all();
        $select_nested_menu_result = Helper::nested($mod_imagecategories_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_menu_result', $select_nested_menu_result);
    }

    // Düzenleme Sayfası
    public function edit($id = NULL)
    {
        // Düzenleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_imagecategories', 'edit', $this->lang['mod_imagecategories']['role_errors']['edit'], 1) === False) {
            return False;
        }
        $this->mod_imagecategories_model = Load::model('mod_imagecategories');
        $id = Req::Url_Clear($id);
        if ($this->mod_imagecategories_model->exists_id($id) === False) {
            $this->_error($this->lang['mod_imagecategories']['errors']['record']);
            Run::import('management', '_index');
            $this->tema->tpl('mod_imagecategories/edit');
            return;
        }

        $d = $this->mod_imagecategories_model->getResultOne()->toArray();

        // Eğer Eklenen İçerik Başkasınınsa Yönetme Permissionsi Varmı ?
        if ($this->user->checkPermission('mod_imagecategories', 'moderate_edit', $d['user_id'], $this->lang['mod_imagecategories']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id);
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_imagecategories']['edit'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        $this->tema->tpl('mod_imagecategories/edit');
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY seo DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $mod_imagecategories_nested = Load::model('mod_imagecategories');
        $mod_imagecategories_nested->flush();
        $mod_imagecategories_nested->order('order', 'ASC')->all();
        $select_nested_parent_id_result = Helper::nested($mod_imagecategories_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_parent_id_result', $select_nested_parent_id_result);
        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY id DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());
    }

    // Listeleme Sayfası
    public function list()
    {
        // Listeleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_imagecategories', 'list', $this->lang['mod_imagecategories']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_imagecategories']['list'];
        $this->tema->tpl('mod_imagecategories/list');
        $this->tema->veri('site', $this->site);
        $mod_imagecategories_nested = Load::model('mod_imagecategories');
        $mod_imagecategories_nested->order('order', 'ASC')->all();
        $select_nested_mod_imagecategories_result = Helper::nested($mod_imagecategories_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_mod_imagecategories_result', $select_nested_mod_imagecategories_result);
    }
    // mod_imagecategories Order Change
    public function _order_change()
    {
        $b = Req::Post_Safe('order');
        $mod_imagecategories_model = new mod_imagecategories_model();
        $x = 1;
        foreach ($b as $a) {
            $mod_imagecategories_model->id = $a;
            $mod_imagecategories_model->order = $x;
            if ($mod_imagecategories_model->update('id') === False) {
                //print_r($mod_imagecategories_model,TRUE);
                return FALSE;
            }
            $x++;
        }
        return TRUE;
    }


    // mod_imagecategories Taşıma
    public function move()
    {
        // Taşıma Permissionsi varmı?
        if ($this->user->hasPermission('mod_imagecategories', 'edit', $this->lang['mod_imagecategories']['role_errors']['edit']) === False) {
            return False;
        }
        $mod_imagecategories_model = Load::model('mod_imagecategories');
        $mod_imagecategories_model->id = Req::Post_Safe('id');
        $mod_imagecategories_model->parent_id = Req::Post_Safe('parent_id');
        if ($mod_imagecategories_model->update('id')  and $this->_order_change()) {
            echo 'OK';
        } else {
            echo 'Error';
            //print_r($mod_imagecategories_model);
        }
    }
    public function image_remove_image()
    {
        // Düzenleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_imagecategories', 'edit', $this->lang['mod_imagecategories']['role_errors']['edit'], 1) === False) {
            return FALSE;
        }

        $id = intval(Req::Post_Safe('id'));

        $this->mod_imagecategories_model = Load::model('mod_imagecategories');
        $this->mod_imagecategories_model->where_id($id)->gRo();
        if ($this->mod_imagecategories_model->getSize() === 0) {
            echo $this->lang['mod_imagecategories']['errors']['record'];
            return FALSE;
        }


        $this->mod_imagecategories_model->sync();
        // Eğer Eklenen İçerik Başkasınınsa Yönetme Permissionsi Varmı ?
        if ($this->user->checkPermission('mod_imagecategories', 'moderate_edit', $this->mod_imagecategories_model->user_id, $this->lang['mod_imagecategories']['role_errors']['moderate_edit']) === False) {
            return False;
        }

        if ($this->mod_imagecategories_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_imagecategories';
            $image->url = $this->mod_imagecategories_model->image;
            $option = [
                '/' =>
                [
                    'width' => 400,
                    'height' => NULL,
                    'option' => 'width',
                ],
                '/thumb' =>
                [
                    'width' => 200,
                    'height' => NULL,
                    'option' => 'width',
                ],
            ];
            if ($image->_delete_files($option)) {
                $this->mod_imagecategories_model->image = '';
                $this->mod_imagecategories_model->update('id');
                echo 'OK';
            } else {
                echo $image->error;
                return FALSE;
            }
        } else {
            echo 'OK';
        }
    }

    // Ekleme Fonksiyonu
    private function _add()
    {
        $this->val = Load::validator('mod_imagecategories.add');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_imagecategories_model = Load::model('mod_imagecategories');

            $this->mod_imagecategories_model->lang = Req::Post_Safe('lang');
            $this->mod_imagecategories_model->name = Req::Post_Safe('name');









            $this->mod_imagecategories_model->content = Req::Post_Safe('content');

            $this->mod_imagecategories_model->parent_id = Req::Post_Safe('parent_id');
            $this->mod_imagecategories_model->root = Req::Post_Safe('root');
            $this->mod_imagecategories_model->seo = Req::Post_Safe('seo');
            $this->mod_imagecategories_model->title = Req::Post_Safe('title');
            $this->mod_imagecategories_model->keywords = Req::Post_Safe('keywords');
            $this->mod_imagecategories_model->description = Req::Post_Safe('description');
            $this->mod_imagecategories_model->acl_id = Req::Post_Safe('acl_id');
            $this->mod_imagecategories_model->user_id = $this->session->getir('id');

            // Eğer Resim varsa
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_imagecategories';
                $image->name = 'image';
                $option = [
                    '/' =>
                    [
                        'width' => 400,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                    '/thumb' =>
                    [
                        'width' => 200,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                ];
                if ($image->_resize($option)) {
                    $this->mod_imagecategories_model->image = $image->url;
                } else {
                    $this->_error_message($image->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_imagecategories_model->image)) {
                    unset($this->mod_imagecategories_model->image);
                }
            }

            if ($this->mod_imagecategories_model->save(TRUE)) {
                // Eğer Menüye Eklemek istemişse
                if (Req::Post_Safe('menu') != 0) {
                    $this->model = Load::model('mod_menu');
                    $this->model->parent_id = Req::Post_Safe('menu');
                    $this->model->name = $this->mod_imagecategories_model->name;
                    $this->model->lang = $this->mod_imagecategories_model->lang;
                    $this->model->url = $this->mod_imagecategories_model->seo;
                    $this->model->acl_id = $this->mod_imagecategories_model->acl_id;
                    $this->model->url = $this->mod_imagecategories_model->lang . '/rkat/' . $this->model->url;
                    $this->model->save();
                }
                $this->_ok_message($this->lang['mod_imagecategories']['message']['ok']['add']);
            } else {
                // Eğer Resim varsa
                if (isset($image->url)) {
                    $image->_rollback();
                }

                $this->_error_message($this->lang['mod_imagecategories']['message']['oke']['add']);
            }
        }
    }

    // Düzenleme Fonksiyonu
    private function _edit($id = NULL)
    {
        $this->val = Load::validator('mod_imagecategories.edit');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_imagecategories_model = Load::model('mod_imagecategories');

            $this->mod_imagecategories_model->id = $id;
            $this->mod_imagecategories_model->lang = Req::Post_Safe('lang');
            $this->mod_imagecategories_model->name = Req::Post_Safe('name');





            $this->mod_imagecategories_model->content = Req::Post_Safe('content');

            $this->mod_imagecategories_model->parent_id = Req::Post_Safe('parent_id');
            $this->mod_imagecategories_model->root = Req::Post_Safe('root');
            $this->mod_imagecategories_model->seo = Req::Post_Safe('seo');
            $this->mod_imagecategories_model->title = Req::Post_Safe('title');
            $this->mod_imagecategories_model->keywords = Req::Post_Safe('keywords');
            $this->mod_imagecategories_model->description = Req::Post_Safe('description');
            $this->mod_imagecategories_model->acl_id = Req::Post_Safe('acl_id');
            // Bu İçerik Daha Önce Menüye Gönderilmişse Güncelleyelim
            $new_mod_imagecategories = new mod_imagecategories_model();
            $new_mod_imagecategories->where_id($id)->gRo()->sync();
            $url = $new_mod_imagecategories->lang . '/rkat/' . $new_mod_imagecategories->seo;

            if (isset($this->mod_imagecategories_model->user_id)) {
                unset($this->mod_imagecategories_model->user_id);
            }

            // Eğer Resim Varsa
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_imagecategories';
                $image->name = 'image';
                $option = [
                    '/' =>
                    [
                        'width' => 400,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                    '/thumb' =>
                    [
                        'width' => 200,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                ];
                if ($image->_resize($option)) {
                    $this->mod_imagecategories_model->image = $image->url;
                } else {
                    $this->_error_message($image->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_imagecategories_model->image)) {
                    unset($this->mod_imagecategories_model->image);
                }
            }

            $this->mod_imagecategories_model->id = $id;
            if ($this->mod_imagecategories_model->update('id')) {
                // Bu İçerik Daha Önce Menüye Gönderilmişse Güncelleyelim
                $mod_menu_model = Load::model('mod_menu');
                if ($mod_menu_model->exists_url($url)) {
                    $mod_menu_model->getResultOne()->sync();
                    $mod_menu_model->name = $this->mod_imagecategories_model->name;
                    $mod_menu_model->lang = $this->mod_imagecategories_model->lang;
                    $mod_menu_model->url = $this->mod_imagecategories_model->seo;
                    $mod_menu_model->acl_id = $this->mod_imagecategories_model->acl_id;
                    $mod_menu_model->url = $this->mod_imagecategories_model->lang . '/rkat/' . $mod_menu_model->url;
                    $mod_menu_model->update();
                }


                $this->_ok_message($this->lang['mod_imagecategories']['message']['ok']['edit']);
            } else {
                // Eğer Resim varsa
                if (isset($image->url)) {
                    $image->_rollback();
                }
                $this->_error_message($this->lang['mod_imagecategories']['message']['error']['edit']);
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
        if ($this->user->hasPermission('mod_imagecategories',  'delete', $this->lang['mod_imagecategories']['role_errors'][ 'delete'], 1) === False) {
            return False;
        }
        $sil = Req::Post_Safe( 'delete');
        if ($this->_dragdrop_delete($sil) === TRUE) {
            echo 'OK';
        } else {
            echo 'ERROR';
            //print_r($this->mod_imagecategories_model);
        }
    }

    // Silme Fonksiyonu
    private function _dragdrop_delete($sil = NULL)
    {
        // mod_imagecategories Modeli Yükleyelim
        $this->mod_imagecategories_model = Load::model('mod_imagecategories');

        $this->mod_imagecategories_model->getid($sil)->getResultOne(TRUE, TRUE);
        if ($this->mod_imagecategories_model->getSize() === 0) {
            print($this->lang['mod_imagecategories']['errors']['record']);
            return FALSE;
        }
        $this->mod_imagecategories_model->sync();

        // Kayıt Başka birine ait ise Permissionsi varmı?
        if ($this->user->checkPermission('mod_imagecategories', 'moderate_delete', $this->mod_imagecategories_model->user_id, $this->lang['mod_imagecategories']['role_errors']['moderate_delete']) === False) {
            return False;
        }
        $new_model = new mod_imagecategories_model();
        if ($new_model->exists_parent_id($sil)) {
            foreach ($new_model->toArray() as $s) {
                if ($this->_dragdrop_delete($s['id']) === TRUE) {
                    continue;
                }
                return FALSE;
            }
        }


        if ($this->mod_imagecategories_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_imagecategories';
            $image->name = 'image';
            $image->url = $this->mod_imagecategories_model->image;
            $option = [
                '/' =>
                [
                    'width' => 400,
                    'height' => NULL,
                    'option' => 'width',
                ],
                '/thumb' =>
                [
                    'width' => 200,
                    'height' => NULL,
                    'option' => 'width',
                ],
            ];
            if ($image->_delete_files($option) !== TRUE) {
                echo $image->error;
            }
        }


        $this->mod_imagecategories_model->getid($sil);
        // Modül varmı ve Gallery Ekleniyormu
        $this->mod = Load::model('mod_gallery_mod');
        if ($this->mod->exists_mod_name("mod_imagecategories") === TRUE) {
            // İçeriğe Ait Gallery Varsa Silelim
            Run::import('mod_gallery', '_remove', ['mod_imagecategories', $this->mod_imagecategories_model->id]);
        }
        // Modül varmı ve Dosya Ekleniyormu
        $this->mod = Load::model('mod_attachment_mod');
        if ($this->mod->exists_mod_name("mod_imagecategories") === TRUE) {
            // İçeriğe Ait Dosya Varsa Silelim
            Run::import('mod_attachment', '_remove', ['mod_imagecategories', $this->mod_imagecategories_model->id]);
        }

        $this->mod_imagecategories_model->delete();
        return TRUE;
    }



    // Valid Message
    private function _ok_message($message)
    {
        $this->tema->veri('ok', True);
        $this->tema->veri('message', $message);
    }

    // Error Message
    private function _error_message($message)
    {
        $this->tema->veri('error', True);
        $this->tema->veri('errors', [$message]);
    }

    // Error Message
    private function _error($message)
    {
        $this->tema->veri('error', TRUE);
        $this->tema->veri('message', $message);
    }
}
