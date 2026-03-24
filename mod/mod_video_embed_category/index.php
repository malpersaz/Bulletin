<?php
/* AKM Software.
 * @2015
 * mod_video_embed_category Modül Sayfası
 * mod/mod_video_embed_category/index.php
 */
class mod_video_embed_category_mod extends indexMain
{
    public $mod_video_embed_category_model;
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
        $this->lang = Load::lang('mod_video_embed_category');
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
        if ($this->user->hasPermission('mod_video_embed_category', 'add', $this->lang['mod_video_embed_category']['role_errors']['add'], 1) === False) {
            return False;
        }
        if (Req::is_Post('add')) {
            $this->_add();
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_video_embed_category']['add'];
        $this->tema->tpl('mod_video_embed_category/add');
        $this->tema->veri('site', $this->site);
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY name DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $mod_video_embed_category_nested = Load::model('mod_video_embed_category');
        $mod_video_embed_category_nested->order('order', 'ASC')->all();
        $select_nested_parent_id_result = Helper::nested($mod_video_embed_category_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_parent_id_result', $select_nested_parent_id_result);
        $mod_video_embed_category_nested = Load::model('mod_menu');
        $mod_video_embed_category_nested->order('order', 'ASC')->all();
        $select_nested_menu_result = Helper::nested($mod_video_embed_category_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_menu_result', $select_nested_menu_result);
    }

    // Düzenleme Sayfası
    public function edit($id = NULL)
    {
        // Düzenleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_video_embed_category', 'edit', $this->lang['mod_video_embed_category']['role_errors']['edit'], 1) === False) {
            return False;
        }
        $this->mod_video_embed_category_model = Load::model('mod_video_embed_category');
        $id = Req::Url_Clear($id);
        if ($this->mod_video_embed_category_model->exists_id($id) === False) {
            $this->_error($this->lang['mod_video_embed_category']['errors']['record']);
            Run::import('management', '_index');
            $this->tema->tpl('mod_video_embed_category/edit');
            return;
        }

        $d = $this->mod_video_embed_category_model->getResultOne()->toArray();

        // Eğer Eklenen İçerik Başkasınınsa Yönetme Permissionsi Varmı ?
        if ($this->user->checkPermission('mod_video_embed_category', 'moderate_edit', $d['user_id'], $this->lang['mod_video_embed_category']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id);
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_video_embed_category']['edit'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        $this->tema->tpl('mod_video_embed_category/edit');
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY name DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $mod_video_embed_category_nested = Load::model('mod_video_embed_category');
        $mod_video_embed_category_nested->flush();
        $mod_video_embed_category_nested->order('order', 'ASC')->all();
        $select_nested_parent_id_result = Helper::nested($mod_video_embed_category_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_parent_id_result', $select_nested_parent_id_result);
    }

    // Listeleme Sayfası
    public function list()
    {
        // Listeleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_video_embed_category', 'list', $this->lang['mod_video_embed_category']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_video_embed_category']['list'];
        $this->tema->tpl('mod_video_embed_category/list');
        $this->tema->veri('site', $this->site);
        $mod_video_embed_category_nested = Load::model('mod_video_embed_category');
        $mod_video_embed_category_nested->order('order', 'ASC')->all();
        $select_nested_mod_video_embed_category_result = Helper::nested($mod_video_embed_category_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_mod_video_embed_category_result', $select_nested_mod_video_embed_category_result);
    }
    // mod_video_embed_category Sıra Değişim
    public function _sira_degis()
    {
        $b = Req::Post_Safe('order');
        $mod_video_embed_category_model = new mod_video_embed_category_model();
        $x = 1;
        foreach ($b as $a) {
            $mod_video_embed_category_model->id = $a;
            $mod_video_embed_category_model->order = $x;
            if ($mod_video_embed_category_model->update('id') === False) {
                //print_r($mod_video_embed_category_model,TRUE);
                return FALSE;
            }
            $x++;
        }
        return TRUE;
    }


    // mod_video_embed_category Taşıma
    public function tasi()
    {
        // Taşıma Permissionsi varmı?
        if ($this->user->hasPermission('mod_video_embed_category', 'edit', $this->lang['mod_video_embed_category']['role_errors']['edit']) === False) {
            return False;
        }
        $mod_video_embed_category_model = Load::model('mod_video_embed_category');
        $mod_video_embed_category_model->id = Req::Post_Safe('id');
        $mod_video_embed_category_model->parent_id = Req::Post_Safe('parent_id');
        if ($mod_video_embed_category_model->update('id')  and $this->_order_change()) {
            echo 'OK';
        } else {
            echo 'Error';
            //print_r($mod_video_embed_category_model);
        }
    }
    public function image_remove_image()
    {
        // Düzenleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_video_embed_category', 'edit', $this->lang['mod_video_embed_category']['role_errors']['edit'], 1) === False) {
            return FALSE;
        }

        $id = intval(Req::Post_Safe('id'));

        $this->mod_video_embed_category_model = Load::model('mod_video_embed_category');
        $this->mod_video_embed_category_model->where_id($id)->gRo();
        if ($this->mod_video_embed_category_model->getSize() === 0) {
            echo $this->lang['mod_video_embed_category']['errors']['record'];
            return FALSE;
        }


        $this->mod_video_embed_category_model->sync();
        // Eğer Eklenen İçerik Başkasınınsa Yönetme Permissionsi Varmı ?
        if ($this->user->checkPermission('mod_video_embed_category', 'moderate_edit', $this->mod_video_embed_category_model->user_id, $this->lang['mod_video_embed_category']['role_errors']['moderate_edit']) === False) {
            return False;
        }

        if ($this->mod_video_embed_category_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_video_embed_category';
            $image->url = $this->mod_video_embed_category_model->image;
            $option = [
                '/' =>
                [
                    'width' => 200,
                    'height' => NULL,
                    'option' => 'width',
                ],
                '/thumb' =>
                [
                    'width' => 150,
                    'height' => 90,
                    'option' => 'extract',
                ],
            ];
            if ($image->_delete_files($option)) {
                $this->mod_video_embed_category_model->image = '';
                $this->mod_video_embed_category_model->update('id');
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
        $this->val = Load::validator('mod_video_embed_category.add');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_video_embed_category_model = Load::model('mod_video_embed_category');

            $this->mod_video_embed_category_model->lang = Req::Post_Safe('lang');
            $this->mod_video_embed_category_model->name = Req::Post_Safe('name');









            $this->mod_video_embed_category_model->parent_id = Req::Post_Safe('parent_id');
            $this->mod_video_embed_category_model->root = Req::Post_Safe('root');
            $this->mod_video_embed_category_model->seo = Req::Post_Safe('seo');
            $this->mod_video_embed_category_model->title = Req::Post_Safe('title');
            $this->mod_video_embed_category_model->keywords = Req::Post_Safe('keywords');
            $this->mod_video_embed_category_model->description = Req::Post_Safe('description');
            $this->mod_video_embed_category_model->user_id = $this->session->getir('id');

            // Eğer Resim varsa
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_video_embed_category';
                $image->name = 'image';
                $option = [
                    '/' =>
                    [
                        'width' => 200,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                    '/thumb' =>
                    [
                        'width' => 150,
                        'height' => 90,
                        'option' => 'extract',
                    ],
                ];
                if ($image->_resize($option)) {
                    $this->mod_video_embed_category_model->image = $image->url;
                } else {
                    $this->_error_message($image->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_video_embed_category_model->image)) {
                    unset($this->mod_video_embed_category_model->image);
                }
            }

            if ($this->mod_video_embed_category_model->save(TRUE)) {
                // Eğer Menüye Eklemek istemişse
                if (Req::Post_Safe('menu') != 0) {
                    $this->model = Load::model('mod_menu');
                    $this->model->parent_id = Req::Post_Safe('menu');
                    $this->model->name = $this->mod_video_embed_category_model->name;
                    $this->model->lang = $this->mod_video_embed_category_model->lang;
                    $this->model->url = $this->mod_video_embed_category_model->seo;
                    $this->model->url = $this->mod_video_embed_category_model->lang . '/videos/' . $this->model->url;
                    $this->model->save();
                }
                $this->_oke_message($this->lang['mod_video_embed_category']['message']['oke']['add']);
            } else {
                // Eğer Resim varsa
                if (isset($image->url)) {
                    $image->_rollback();
                }

                $this->_error_message($this->lang['mod_video_embed_category']['message']['oke']['add']);
            }
        }
    }

    // Düzenleme Fonksiyonu
    private function _edit($id = NULL)
    {
        $this->val = Load::validator('mod_video_embed_category.edit');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_video_embed_category_model = Load::model('mod_video_embed_category');

            $this->mod_video_embed_category_model->id = $id;
            $this->mod_video_embed_category_model->lang = Req::Post_Safe('lang');
            $this->mod_video_embed_category_model->name = Req::Post_Safe('name');





            $this->mod_video_embed_category_model->parent_id = Req::Post_Safe('parent_id');
            $this->mod_video_embed_category_model->root = Req::Post_Safe('root');
            $this->mod_video_embed_category_model->seo = Req::Post_Safe('seo');
            $this->mod_video_embed_category_model->title = Req::Post_Safe('title');
            $this->mod_video_embed_category_model->keywords = Req::Post_Safe('keywords');
            $this->mod_video_embed_category_model->description = Req::Post_Safe('description');
            // Bu İçerik Daha Önce Menüye Gönderilmişse Güncelleyelim
            $new_mod_video_embed_category = new mod_video_embed_category_model();
            $new_mod_video_embed_category->where_id($id)->gRo()->sync();
            $url = $new_mod_video_embed_category->lang . '/videos/' . $new_mod_video_embed_category->seo;

            if (isset($this->mod_video_embed_category_model->user_id)) {
                unset($this->mod_video_embed_category_model->user_id);
            }

            // Eğer Resim Varsa
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_video_embed_category';
                $image->name = 'image';
                $option = [
                    '/' =>
                    [
                        'width' => 200,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                    '/thumb' =>
                    [
                        'width' => 150,
                        'height' => 90,
                        'option' => 'extract',
                    ],
                ];
                if ($image->_resize($option)) {
                    $this->mod_video_embed_category_model->image = $image->url;
                } else {
                    $this->_error_message($image->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_video_embed_category_model->image)) {
                    unset($this->mod_video_embed_category_model->image);
                }
            }

            $this->mod_video_embed_category_model->id = $id;
            if ($this->mod_video_embed_category_model->update('id')) {
                // Bu İçerik Daha Önce Menüye Gönderilmişse Güncelleyelim
                $mod_menu_model = Load::model('mod_menu');
                if ($mod_menu_model->exists_url($url)) {
                    $mod_menu_model->getResultOne()->sync();
                    $mod_menu_model->name = $this->mod_video_embed_category_model->name;
                    $mod_menu_model->lang = $this->mod_video_embed_category_model->lang;
                    $mod_menu_model->url = $this->mod_video_embed_category_model->seo;
                    $mod_menu_model->url = $this->mod_video_embed_category_model->lang . '/videos/' . $mod_menu_model->url;
                    $mod_menu_model->update();
                }


                $this->_oke_message($this->lang['mod_video_embed_category']['message']['oke']['edit']);
            } else {
                // Eğer Resim varsa
                if (isset($image->url)) {
                    $image->_rollback();
                }
                $this->_error_message($this->lang['mod_video_embed_category']['message']['error']['edit']);
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
        if ($this->user->hasPermission('mod_video_embed_category',  'delete', $this->lang['mod_video_embed_category']['role_errors'][ 'delete'], 1) === False) {
            return False;
        }
        $sil = Req::Post_Safe( 'delete');
        if ($this->_dragdrop_delete($sil) === TRUE) {
            echo 'OK';
        } else {
            echo 'ERROR';
            //print_r($this->mod_video_embed_category_model);
        }
    }

    // Silme Fonksiyonu
    private function _dragdrop_delete($delete = NULL)
    {
        // mod_video_embed_category Modeli Yükleyelim
        $this->mod_video_embed_category_model = Load::model('mod_video_embed_category');

        $this->mod_video_embed_category_model->getid($delete)->getResultOne(TRUE, TRUE);
        if ($this->mod_video_embed_category_model->getSize() === 0) {
            print($this->lang['mod_video_embed_category']['errors']['record']);
            return FALSE;
        }
        $this->mod_video_embed_category_model->sync();

        // Kayıt Başka birine ait ise Permissionsi varmı?
        if ($this->user->checkPermission('mod_video_embed_category', 'moderate_delete', $this->mod_video_embed_category_model->user_id, $this->lang['mod_video_embed_category']['role_errors']['moderate_delete']) === False) {
            return False;
        }
        $new_model = new mod_video_embed_category_model();
        if ($new_model->exists_parent_id($delete)) {
            foreach ($new_model->toArray() as $s) {
                if ($this->_dragdrop_delete($s['id']) === TRUE) {
                    continue;
                }
                return FALSE;
            }
        }


        if ($this->mod_video_embed_category_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_video_embed_category';
            $image->name = 'image';
            $image->url = $this->mod_video_embed_category_model->image;
            $option = [
                '/' =>
                [
                    'width' => 200,
                    'height' => NULL,
                    'option' => 'width',
                ],
                '/thumb' =>
                [
                    'width' => 150,
                    'height' => 90,
                    'option' => 'extract',
                ],
            ];
            if ($image->_delete_files($option) !== TRUE) {
                echo $image->error;
            }
        }


        $this->mod_video_embed_category_model->getid($delete);
        // Modül varmı ve Gallery Ekleniyormu
        $this->mod = Load::model('mod_gallery_mod');
        if ($this->mod->exists_mod_name("mod_video_embed_category") === TRUE) {
            // İçeriğe Ait Gallery Varsa Silelim
            Run::import('mod_gallery', '_remove', ['mod_video_embed_category', $this->mod_video_embed_category_model->id]);
        }
        // Modül varmı ve Dosya Ekleniyormu
        $this->mod = Load::model('mod_attachment_mod');
        if ($this->mod->exists_mod_name("mod_video_embed_category") === TRUE) {
            // İçeriğe Ait Dosya Varsa Silelim
            Run::import('mod_attachment', '_remove', ['mod_video_embed_category', $this->mod_video_embed_category_model->id]);
        }

        $this->mod_video_embed_category_model->delete();
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
