<?php
/* AKM Software.
 * @2010
 * mod_slider Modül Sayfası
 * mod/mod_slider/index.php
 */
class mod_slider_mod extends indexMain
{
    public $mod_slider_model;
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
        $this->lang = Load::lang('mod_slider');
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
        if ($this->user->hasPermission('mod_slider', 'add', $this->lang['mod_slider']['role_errors']['add'], 1) === False) {
            return False;
        }
        if (Req::is_Post('add')) {
            $this->_add();
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_slider']['add'];
        $this->tema->tpl('mod_slider/add');
        $this->tema->veri('site', $this->site);
        $this->db->fetch_array("SELECT name,seo FROM `lang` ORDER BY seo DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $this->db->fetch_array("SELECT name,id FROM `mod_acl` ORDER BY id DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());
    }

    // Düzenleme Sayfası
    public function edit($id = NULL)
    {
        // Düzenleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_slider', 'edit', $this->lang['mod_slider']['role_errors']['edit'], 1) === False) {
            return False;
        }
        $this->mod_slider_model = Load::model('mod_slider');
        $id = Req::Url_Clear($id);
        if ($this->mod_slider_model->exists_id($id) === False) {
            $this->_error($this->lang['mod_slider']['errors']['record']);
            Run::import('management', '_index');
            $this->tema->tpl('mod_slider/edit');
            return;
        }

        $d = $this->mod_slider_model->getResultOne()->toArray();

        // Eğer Eklenen İçerik Başkasınınsa Yönetme Permissionsi Varmı ?
        if ($this->user->checkPermission('mod_slider', 'moderate_edit', $d['user_id'], $this->lang['mod_slider']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id);
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_slider']['edit'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        $this->tema->tpl('mod_slider/edit');
        $this->db->fetch_array("SELECT name,seo FROM `lang` ORDER BY seo DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $this->db->fetch_array("SELECT name,id FROM `mod_acl` ORDER BY id DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());
    }

    // Listeleme Sayfası
    public function list()
    {
        // Listeleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_slider', 'list', $this->lang['mod_slider']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_slider']['list'];
        $this->tema->tpl('mod_slider/list');
        $this->tema->veri('site', $this->site);
    }
    public function image_remove_image()
    {
        // Düzenleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_slider', 'edit', $this->lang['mod_slider']['role_errors']['edit'], 1) === False) {
            return FALSE;
        }

        $id = intval(Req::Post_Safe('id'));

        $this->mod_slider_model = Load::model('mod_slider');
        $this->mod_slider_model->where_id($id)->gRo();
        if ($this->mod_slider_model->getSize() === 0) {
            echo $this->lang['mod_slider']['errors']['record'];
            return FALSE;
        }


        $this->mod_slider_model->sync();
        // Eğer Eklenen İçerik Başkasınınsa Yönetme Permissionsi Varmı ?
        if ($this->user->checkPermission('mod_slider', 'moderate_edit', $this->mod_slider_model->user_id, $this->lang['mod_slider']['role_errors']['moderate_edit']) === False) {
            return False;
        }

        $isDeleted = false;
        $type = Req::Post_Safe('type');

        if (($type == 'desk' || $type == '') && $this->mod_slider_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_slider';
            $image->url = $this->mod_slider_model->image;
            $option = [
                '/' =>
                [
                    'width' => 1920,
                    'height' => 1080,
                    'option' => 'width',
                ],
                '/thumb' =>
                [
                    'width' => 100,
                    'height' => NULL,
                    'option' => 'width',
                ],
            ];
            if ($image->_delete_files($option)) {
                $this->mod_slider_model->image = '';
                $isDeleted = true;
            } else {
                echo $image->error;
                return FALSE;
            }
        }
        
        if (($type == 'mob' || $type == '') && $this->mod_slider_model->image_mobile != '') {
            $imageMobile = Load::mod('image');
            $imageMobile->mod = 'mod_slider';
            $imageMobile->url = $this->mod_slider_model->image_mobile;
            $optionMobile = [
                '/' =>
                [
                    'width' => 1080,
                    'height' => null,
                    'option' => 'width',
                ]
            ];
            if ($imageMobile->_delete_files($optionMobile)) {
                $this->mod_slider_model->image_mobile = '';
                $isDeleted = true;
            } else {
                echo $imageMobile->error;
                return FALSE;
            }
        }

        if ($isDeleted) {
            $this->mod_slider_model->update('id');
            echo 'Oke';
        } else {
            echo 'Oke';
        }
    }

    // Ekleme Fonksiyonu
    private function _add()
    {
        $this->val = Load::validator('mod_slider.add');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('errors', $this->val->hatalar);
            $this->tema->veri('error', TRUE);
        } else {
            $this->mod_slider_model = Load::model('mod_slider');

            $this->mod_slider_model->lang = Req::Post_Safe('lang');
            $this->mod_slider_model->name = Req::Post_Safe('name');









            $this->mod_slider_model->url = Req::Post_Safe('url');









            $this->mod_slider_model->status = Req::Post_Safe('status');

            $this->mod_slider_model->target = Req::Post_Safe('target');

            $this->mod_slider_model->created_at = str_replace('T', ' ', Req::Post_Safe('created_at'));
            if(empty(trim($this->mod_slider_model->created_at))) {
                $this->mod_slider_model->created_at = date('Y-m-d H:i:s');
            }

            $this->mod_slider_model->description = Req::Post_Safe('description');

            $this->mod_slider_model->acl_id = Req::Post_Safe('acl_id');
            $this->mod_slider_model->user_id = $this->session->getir('id');

            // Eğer Resim varsa
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_slider';
                $image->name = 'image';
                $option = [
                    '/' =>
                    [
                        'width' => 1920,
                        'height' => 1080,
                        'option' => 'width',
                    ],
                    '/thumb' =>
                    [
                        'width' => 100,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                ];
                if ($image->_resize($option)) {
                    $this->mod_slider_model->image = $image->url;
                } else {
                    $this->_error_message($image->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_slider_model->image)) {
                    unset($this->mod_slider_model->image);
                }
            }

            // Eğer Mobile Resim varsa
            if (isset($_FILES['image_mobile']['name']) &&  ($_FILES['image_mobile']['name'] != '')) {
                $imageMobile = Load::mod('image');
                $imageMobile->mod = 'mod_slider';
                $imageMobile->name = 'image_mobile';
                $optionMobile = [
                    '/' =>
                    [
                        'width' => 1080,
                        'height' => null,
                        'option' => 'width',
                    ]
                ];
                if ($imageMobile->_resize($optionMobile)) {
                    $this->mod_slider_model->image_mobile = $imageMobile->url;
                } else {
                    $this->_error_message($imageMobile->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_slider_model->image_mobile)) {
                    unset($this->mod_slider_model->image_mobile);
                }
            }

            if ($this->mod_slider_model->save(TRUE)) {
                $this->_oke_message($this->lang['mod_slider']['message']['oke']['add']);
            } else {
                // Eğer Resim varsa
                if (isset($image->url)) {
                    $image->_rollback();
                }

                $this->_error_message($this->lang['mod_slider']['message']['oke']['add']);
            }
        }
    }

    // Düzenleme Fonksiyonu
    private function _edit($id = NULL)
    {
        $this->val = Load::validator('mod_slider.edit');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('errors', $this->val->hatalar);
            $this->tema->veri('error', TRUE);
        } else {
            $this->mod_slider_model = Load::model('mod_slider');

            $this->mod_slider_model->id = $id;
            $this->mod_slider_model->lang = Req::Post_Safe('lang');
            $this->mod_slider_model->name = Req::Post_Safe('name');





            $this->mod_slider_model->url = Req::Post_Safe('url');





            $this->mod_slider_model->status = Req::Post_Safe('status');

            $this->mod_slider_model->target = Req::Post_Safe('target');

            $this->mod_slider_model->created_at = str_replace('T', ' ', Req::Post_Safe('created_at'));

            $this->mod_slider_model->description = Req::Post_Safe('description');

            $this->mod_slider_model->acl_id = Req::Post_Safe('acl_id');
            if (isset($this->mod_slider_model->user_id)) {
                unset($this->mod_slider_model->user_id);
            }

            // Eğer Resim Varsa
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_slider';
                $image->name = 'image';
                $option = [
                    '/' =>
                    [
                        'width' => 1920,
                        'height' => 1080,
                        'option' => 'width',
                    ],
                    '/thumb' =>
                    [
                        'width' => 100,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                ];
                if ($image->_resize($option)) {
                    $this->mod_slider_model->image = $image->url;
                } else {
                    $this->_error_message($image->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_slider_model->image)) {
                    unset($this->mod_slider_model->image);
                }
            }

            // Eğer Mobile Resim Varsa
            if (isset($_FILES['image_mobile']['name']) &&  ($_FILES['image_mobile']['name'] != '')) {
                $imageMobile = Load::mod('image');
                $imageMobile->mod = 'mod_slider';
                $imageMobile->name = 'image_mobile';
                $optionMobile = [
                    '/' =>
                    [
                        'width' => 1080,
                        'height' => null,
                        'option' => 'width',
                    ]
                ];
                if ($imageMobile->_resize($optionMobile)) {
                    $this->mod_slider_model->image_mobile = $imageMobile->url;
                } else {
                    $this->_error_message($imageMobile->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_slider_model->image_mobile)) {
                    unset($this->mod_slider_model->image_mobile);
                }
            }

            $this->mod_slider_model->id = $id;
            if ($this->mod_slider_model->update('id')) {
                $this->_oke_message($this->lang['mod_slider']['message']['oke']['edit']);
            } else {
                // Eğer Resim varsa
                if (isset($image->url)) {
                    $image->_rollback();
                }
                if (isset($imageMobile->url)) {
                    $imageMobile->_rollback();
                }
                $this->_error_message($this->lang['mod_slider']['message']['error']['edit']);
            }
        }
    }


    public function delete()
    {
        // Listeleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_slider',  'delete', $this->lang['mod_slider']['role_errors'][ 'delete']) === False) {
            return False;
        }
        if (Req::is_Post( 'delete')) {
            if ($this->_delete(Req::Post_Safe( 'delete'))) {
                echo 'Oke';
            } else {
                echo 'HATA';
            }
        } else {
            exit($this->lang['mod_slider']['errors']['record']);
        }
    }

    public function _delete($sil)
    {
        $this->mod_slider_model = Load::model('mod_slider');
        foreach ($sil as $s) {
            $this->mod_slider_model->flush();
            $this->mod_slider_model->where_id($s)->gRo();
            if ($this->mod_slider_model->getSize() === 0) {
                exit($this->lang['mod_slider']['errors']['record']);
            }
            $this->mod_slider_model->sync();
            // Eğer Eklenen İçerik Başkasınınsa Yönetme Permissionsi Varmı ?
            if ($this->user->checkPermission('mod_slider', 'moderate_delete', $this->mod_slider_model->user_id, $this->lang['mod_slider']['role_errors']['moderate_delete']) === False) {
                return False;
            }

            // Modül varmı ve Gallery Ekleniyormu
            $this->mod = Load::model('mod_gallery_mod');
            if ($this->mod->exists_mod_name("mod_slider") === TRUE) {
                // İçeriğe Ait Gallery Varsa Silelim
                Run::import('mod_gallery', '_remove', ['mod_slider', $this->mod_slider_model->id]);
            }

            if ($this->mod_slider_model->image != '') {
                $image = Load::mod('image');
                $image->mod = 'mod_slider';
                $image->name = 'image';
                $image->url = $this->mod_slider_model->image;
                $option = [
                    '/' =>
                    [
                        'width' => 1920,
                        'height' => 1080,
                        'option' => 'width',
                    ],
                    '/thumb' =>
                    [
                        'width' => 100,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                ];
                if ($image->_delete_files($option) !== TRUE) {
                    echo $image->error;
                }
            }

            if ($this->mod_slider_model->image_mobile != '') {
                $imageMobile = Load::mod('image');
                $imageMobile->mod = 'mod_slider';
                $imageMobile->name = 'image_mobile';
                $imageMobile->url = $this->mod_slider_model->image_mobile;
                $optionMobile = [
                    '/' =>
                    [
                        'width' => 1080,
                        'height' => null,
                        'option' => 'width',
                    ]
                ];
                if ($imageMobile->_delete_files($optionMobile) !== TRUE) {
                    echo $imageMobile->error;
                }
            }


            $this->mod_slider_model->delete();
        }
        return TRUE;
    }

    public function _valid_date($data = '')
    {
        if ($data == '') {
            return false;
        }
        return preg_match('@(\d{4})-(\d{2})-(\d{2})$@', $data);
    }


    public function datatable_json()
    {


        $i = Load::model('mod_slider');


        $colomns = [];
        $colomns[] = 'id';
        $colomns[] = 'name';
        $colomns[] = 'image';
        $colomns[] = 'lang';
        $colomns[] = 'created_at';
        $colomns[] = 'status';
        $i->select($colomns);
        $limit = 50;
        $offset = 0;
        $order = $colomns[0];;
        $sort = 'desc';

        if (Req::is_Post('order')) {
            $o = Req::Post_Safe('order');
            if (
                isset($o[0]['column']) && ($o[0]['column'] > 0) && isset($colomns[$o[0]['column'] - 1])

            ) {
                $order = $colomns[$o[0]['column'] - 1];
            }
            if (isset($o[0]['dir']) && in_array($o[0]['dir'], ['asc', 'desc'])) {
                $sort = $o[0]['dir'];
            }
        }
        $i->order($order, $sort);



        $wheres = [];

        if (Req::is_Post('arama') && count(Req::Post_Safe('arama')) > 0) {
            foreach (Req::Post_Safe('arama') as $k => $v) {
                if (in_array($k, $colomns) && $v != '') {
                    $wheres[] = $k . " LIKE '%" . Req::Clear($v) . "%'";
                }
            }
        }

        if (Req::is_Post('arama_radio') && count(Req::Post_Safe('arama_radio')) > 0) {
            foreach (Req::Post_Safe('arama_radio') as $k => $v) {
                if (in_array($k, $colomns) && $v != '') {
                    $wheres[] = $k . "='" . Req::Clear($v) . "'";
                }
            }
        }
        if (Req::is_Post('arama_created_at') && count(Req::Post_Safe('arama_created_at')) > 0) {
            foreach (Req::Post_Safe('arama_created_at') as $k => $v) {
                if (in_array($k, $colomns) && $v != '') {
                    if (isset($v['from']) && isset($v['to']) && $this->_valid_date($v['from']) && $this->_valid_date($v['to'])) {
                        $wheres[] = $k . " BETWEEN '" . $v['from'] . "' AND '" . $v['to'] . "'";
                    } elseif (
                        isset($v['from']) && $this->_valid_date($v['from'])
                    ) {
                        $wheres[] = $k . " > '" . $v['from'] . "'";
                    } elseif (
                        isset($v['to']) && $this->_valid_date($v['to'])
                    ) {
                        $wheres[] = $k . " < '" . $v['to'] . "'";
                    }
                }
            }
        }
        if (
            (Roll::hasPermission('mod_slider', 'moderate_edit') !== TRUE)
            and
            (Roll::hasPermission('mod_slider', 'moderate_delete') !== TRUE)
        ) {
            $wheres[] = "user_id='" . $this->session->getir('id') . "'";
        }

        if (count($wheres) > 0) {
            $wheres = "(" . implode(") AND (", $wheres) . ")";
            $i->addWhere($wheres);
        }



        if (Req::is_Post('length') && intval(Req::Post_Safe('length'))) {
            $limit = intval(Req::Post_Safe('length'));
        }


        if (Req::is_Post('start') && intval(Req::Post_Safe('start'))) {
            $offset = intval(Req::Post_Safe('start'));
        }
        $getCount = $i->getCount();
        $i->limit($limit, $offset);


        $i->gR();
        //print_r($i);exit;
        $data = [];














        $status_list = [


            'y' => ['success', $this->lang['mod_slider']['admin']['radio']['publish_yes']],




            'n' => ['danger', $this->lang['mod_slider']['admin']['radio']['publish_no']],



        ];




        $target_list = [


            'y' => ['success', $this->lang['mod_slider']['admin']['radio']['target_e']],




            'n' => ['danger', $this->lang['mod_slider']['admin']['radio']['target_h']],



        ];













        if ($getCount > 0) {
            foreach ($i->toArray() as $content) {

                $last = "";
                if (Roll::hasPermission('mod_slider', 'edit')) {
                    $last .= '<button onclick="mod_slider_table_ajax.akm.processEdit(' . $content['id'] . ');return false;" class="btn default btn-xs purple">
    <i class="fa fa-edit"></i> ' . $this->lang['mod_slider']['button']['edit'] . ' </button>';
                }
                if (Roll::hasPermission('mod_slider',  'delete')) {
                    $last .= '<button onclick="mod_slider_table_ajax.akm.processSil(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs red">
    <i class="fa fa-trash-o"></i> ' . $this->lang['mod_slider']['button'][ 'delete'] . ' </button>';
                }

                if (Roll::hasPermission('mod_slider', 'gallery')) {
                    $last .= '<button onclick="mod_slider_table_ajax.akm.processGallery(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs blue">
    <i class="fa fa-file-image-o"></i> ' . $this->lang['mod_slider']['button']['gallery'] . ' </button>';
                }

                if (Roll::hasPermission('mod_slider', 'file')) {
                    $last .= '<button onclick="mod_slider_table_ajax.akm.processDosya(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs green">
    <i class="fa fa-file-zip-o"></i> ' . $this->lang['mod_slider']['button']['file'] . ' </button>';
                }
                $data[] = [
                    '<input type="checkbox" name="id[]" value="' . $content['id'] . '">',
                    $content['id'],
                    $content['name'],
                    '<img src="' . $this->site['radres'] . 'mod_slider/thumb/' . $content['image'] . '">',
                    $content['lang'],
                    $content['created_at'],
                    '<span class="label label-' . $status_list[$content['status']]['0'] . '">' . $status_list[$content['status']]['1'] . '</span>',
                    $last
                ];
            }
        }
        $ii = new mod_slider_model();
        echo json_encode([
            'data' => $data,
            'recordsFiltered' => $getCount,
            'recordsTotal' => $ii->getCount(),
            // 'customActionStatus'=>'OK',
            ///'customActionMessage'=>'Oldumu Şimdi?'
        ]);
    }





    // publish Sayfası
    public function publish()
    {
        // duzenle Permissionsi varmı?
        if ($this->user->hasPermission('mod_slider', 'edit', $this->lang['mod_slider']['role_errors']['edit']) === False) {
            return False;
        }
        $query = Req::Post_Safe('query');
        if ($this->_publish($query) === TRUE) {
            echo 'Oke';
        } else {
            echo 'ERROR';
        }
    }

    private function _publish($query = NULL)
    {
        // mod_slider Modeli Yükleyelim
        $this->mod_slider_model = Load::model('mod_slider');
        if (is_array($query)) {
            foreach ($query as $s) {
                $this->mod_slider_model->id = $s;
                $this->mod_slider_model->status = 'y';
                if ($this->mod_slider_model->update('id')) {
                    continue;
                }
                return FALSE;
            }
            return TRUE;
        }
        return FALSE;
    }
    // unpublish Sayfası
    public function unpublish()
    {
        // duzenle Permissionsi varmı?
        if ($this->user->hasPermission('mod_slider', 'edit', $this->lang['mod_slider']['role_errors']['edit']) === False) {
            return False;
        }
        $query = Req::Post_Safe('query');
        if ($this->_unpublish($query) === TRUE) {
            echo 'Oke';
        } else {
            echo 'ERROR';
        }
    }

    private function _unpublish($query = NULL)
    {
        // mod_slider Modeli Yükleyelim
        $this->mod_slider_model = Load::model('mod_slider');
        if (is_array($query)) {
            foreach ($query as $s) {
                $this->mod_slider_model->id = $s;
                $this->mod_slider_model->status = 'n';
                if ($this->mod_slider_model->update('id')) {
                    continue;
                }
                return FALSE;
            }
            return TRUE;
        }
        return FALSE;
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
        $this->tema->veri('error', True);
        $this->tema->veri('errors', [$mesaj]);
    }

    // Error Mesaj
    private function _error($mesaj)
    {
        $this->tema->veri('error', TRUE);
        $this->tema->veri('mesaj', $mesaj);
    }
}
