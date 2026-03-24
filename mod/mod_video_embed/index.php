<?php
/* AKM Software.
 * @2015
 * mod_video_embed Modül Sayfası
 * mod/mod_video_embed/index.php
 */
class mod_video_embed_mod extends indexMain
{
    public $mod_video_embed_model;
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
        $this->lang = Load::lang('mod_video_embed');
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
        if ($this->user->hasPermission('mod_video_embed', 'add', $this->lang['mod_video_embed']['role_errors']['add'], 1) === False) {
            return False;
        }
        if (Req::is_Post('add')) {
            $this->_add();
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_video_embed']['add'];
        $this->tema->tpl('mod_video_embed/add');
        $this->tema->veri('site', $this->site);
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY seo DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $mod_video_embed_category_nested = Load::model('mod_video_embed_category');
        $mod_video_embed_category_nested->order('order', 'ASC')->all();
        $select_nested_category_id_result = Helper::nested($mod_video_embed_category_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_category_id_result', $select_nested_category_id_result);
        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY id DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());
    }

    // Düzenleme Sayfası
    public function edit($id = NULL)
    {
        // Düzenleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_video_embed', 'edit', $this->lang['mod_video_embed']['role_errors']['edit'], 1) === False) {
            return False;
        }
        $this->mod_video_embed_model = Load::model('mod_video_embed');
        $id = Req::Url_Clear($id);
        if ($this->mod_video_embed_model->exists_id($id) === False) {
            $this->_error($this->lang['mod_video_embed']['errors']['record']);
            Run::import('management', '_index');
            $this->tema->tpl('mod_video_embed/edit');
            return;
        }

        $d = $this->mod_video_embed_model->getResultOne()->toArray();

        // Eğer Eklenen İçerik Başkasınınsa Yönetme Permissionsi Varmı ?
        if ($this->user->checkPermission('mod_video_embed', 'moderate_edit', $d['user_id'], $this->lang['mod_video_embed']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id);
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_video_embed']['edit'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        $this->tema->tpl('mod_video_embed/edit');
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY seo DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $mod_video_embed_category_nested = Load::model('mod_video_embed_category');
        $mod_video_embed_category_nested->flush();
        $mod_video_embed_category_nested->order('order', 'ASC')->all();
        $select_nested_category_id_result = Helper::nested($mod_video_embed_category_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_category_id_result', $select_nested_category_id_result);
        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY id DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());
    }

    // Listeleme Sayfası
    public function list()
    {
        // Listeleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_video_embed', 'list', $this->lang['mod_video_embed']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_video_embed']['list'];
        $this->tema->tpl('mod_video_embed/list');
        $this->tema->veri('site', $this->site);
    }

    // AJAX ile lîsteyê de lezgîn wêne barkirin
    public function ajax_image_upload()
    {
        // Düzenleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_video_embed', 'edit', $this->lang['mod_video_embed']['role_errors']['edit'], 1) === False) {
            echo json_encode(['status' => 'error', 'message' => 'Permissionniz yok']);
            return;
        }

        $id = intval(Req::Post_Safe('id'));
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'ID eksik']);
            return;
        }

        $this->mod_video_embed_model = Load::model('mod_video_embed');
        $this->mod_video_embed_model->where_id($id)->gRo();
        if ($this->mod_video_embed_model->getSize() === 0) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video_embed']['errors']['record']]);
            return;
        }
        $this->mod_video_embed_model->sync();

        // Eğer Eklenen İçerik Başkasınınsa Yönetme Permissionsi Varmı?
        if ($this->user->checkPermission('mod_video_embed', 'moderate_edit', $this->mod_video_embed_model->user_id, $this->lang['mod_video_embed']['role_errors']['moderate_edit']) === False) {
            echo json_encode(['status' => 'error', 'message' => 'Bu içeriği düzenleme permissionniz yok']);
            return;
        }

        if (isset($_FILES['image']['name']) && ($_FILES['image']['name'] != '')) {
            $image = Load::mod('image');
            $image->mod = 'mod_video_embed';
            $image->name = 'image';
            $option = [
                '/' => ['width' => 225, 'height' => NULL, 'option' => 'width'],
                '/thumb' => ['width' => 100, 'height' => NULL, 'option' => 'width'],
            ];

            try {
                if ($image->_resize($option)) {
                    $new_image_url = $image->url;
                    // Berî ku em wêneyê nû qeyd bikin, yê kevin jê bibin (Heke hebe)
                    if ($this->mod_video_embed_model->image != '') {
                        $image->mod = 'mod_video_embed';
                        $image->url = $this->mod_video_embed_model->image;
                        $image->_delete_files($option);
                    }

                    $update_model = Load::model('mod_video_embed');
                    $update_model->id = $id;
                    $update_model->image = $new_image_url;

                    if ($update_model->update('id')) {
                        echo json_encode([
                            'status' => 'success',
                            'url' => $this->site['radres'] . 'mod_video_embed/thumb/' . $new_image_url . '?v=' . time()
                        ]);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Veritabanı güncellenemedi.']);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => $image->error]);
                }
            } catch (Throwable $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Dosya yüklenmedi']);
        }
    }

    public function image_remove_image()
    {
        // Düzenleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_video_embed', 'edit', $this->lang['mod_video_embed']['role_errors']['edit'], 1) === False) {
            return FALSE;
        }

        $id = intval(Req::Post_Safe('id'));

        $this->mod_video_embed_model = Load::model('mod_video_embed');
        $this->mod_video_embed_model->where_id($id)->gRo();
        if ($this->mod_video_embed_model->getSize() === 0) {
            echo $this->lang['mod_video_embed']['errors']['record'];
            return FALSE;
        }


        $this->mod_video_embed_model->sync();
        // Eğer Eklenen İçerik Başkasınınsa Yönetme Permissionsi Varmı ?
        if ($this->user->checkPermission('mod_video_embed', 'moderate_edit', $this->mod_video_embed_model->user_id, $this->lang['mod_video_embed']['role_errors']['moderate_edit']) === False) {
            return False;
        }

        if ($this->mod_video_embed_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_video_embed';
            $image->url = $this->mod_video_embed_model->image;
            $option = [
                '/' =>
                [
                    'width' => 225,
                    'height' => NULL,
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
                $this->mod_video_embed_model->image = '';
                $this->mod_video_embed_model->update('id');
                echo 'Oke';
            } else {
                echo $image->error;
                return FALSE;
            }
        } else {
            echo 'Oke';
        }
    }

    // Ekleme Fonksiyonu
    private function _add()
    {
        $isAjax = $this->_isAjaxRequest();
        $this->val = Load::validator('mod_video_embed.add');
        if ($this->val->check()) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'error',
                    'message' => implode(', ', $this->val->hatalar),
                    'errors' => $this->val->hatalar
                ]);
                exit;
            }
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_video_embed_model = Load::model('mod_video_embed');

            $this->mod_video_embed_model->lang = Req::Post_Safe('lang');
            $this->mod_video_embed_model->category_id = Req::Post_Safe('category_id');
            $this->mod_video_embed_model->name = Req::Post_Safe('name');
            $this->mod_video_embed_model->original = Req::Post_Safe('original');
            $this->mod_video_embed_model->duration = Req::Post_Safe('sure');
            $this->mod_video_embed_model->created_at = date('Y-m-d H:i:s');
            $this->mod_video_embed_model->status = Req::Post_Safe('status');
            $this->mod_video_embed_model->embed = Req::Post('embed');
            $this->mod_video_embed_model->content = Req::Post('content');
            $this->mod_video_embed_model->content = Helper::clear_html($this->mod_video_embed_model->content);
            $this->mod_video_embed_model->title = Req::Post_Safe('title');
            $this->mod_video_embed_model->keywords = Req::Post_Safe('keywords');
            $this->mod_video_embed_model->description = Req::Post_Safe('description');
            $this->mod_video_embed_model->acl_id = Req::Post_Safe('acl_id');
            $this->mod_video_embed_model->user_id = $this->session->getir('id');

            // Eğer Resim varsa
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_video_embed';
                $image->name = 'image';
                $option = [
                    '/' =>
                    [
                        'width' => 225,
                        'height' => NULL,
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
                    $this->mod_video_embed_model->image = $image->url;
                } else {
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['status' => 'error', 'message' => $image->error]);
                    exit;
                }
                $this->_error_message($image->error);
                return FALSE;
                }
            } else {
                if (isset($this->mod_video_embed_model->image)) {
                    unset($this->mod_video_embed_model->image);
                }
            }

            if ($this->mod_video_embed_model->save(TRUE)) {
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'status' => 'success',
                        'message' => $this->lang['mod_video_embed']['message']['oke']['add'],
                        'redirect' => $this->site['sadres'] . 'mod_video_embed/list'
                    ]);
                    exit;
                }
                $this->_oke_message($this->lang['mod_video_embed']['message']['oke']['add']);
            } else {
                // Eğer Resim varsa
                if (isset($image->url)) {
                    $image->_rollback();
                }

                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video_embed']['message']['error']['add']]);
                    exit;
                }
                $this->_error_message($this->lang['mod_video_embed']['message']['error']['add']);
            }
        }
    }

    // Düzenleme Fonksiyonu
    private function _edit($id = NULL)
    {
        $this->val = Load::validator('mod_video_embed.edit');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_video_embed_model = Load::model('mod_video_embed');

            $this->mod_video_embed_model->id = $id;
            $this->mod_video_embed_model->lang = Req::Post_Safe('lang');
            $this->mod_video_embed_model->category_id = Req::Post_Safe('category_id');
            $this->mod_video_embed_model->name = Req::Post_Safe('name');
            $this->mod_video_embed_model->original = Req::Post_Safe('original');
            $this->mod_video_embed_model->duration = Req::Post_Safe('sure');
            $this->mod_video_embed_model->created_at = Req::Post_Safe('created_at');
            $this->mod_video_embed_model->status = Req::Post_Safe('status');
            $this->mod_video_embed_model->embed = Req::Post('embed');
            $this->mod_video_embed_model->content = Req::Post('content');
            $this->mod_video_embed_model->content = Helper::clear_html($this->mod_video_embed_model->content);
            $this->mod_video_embed_model->title = Req::Post_Safe('title');
            $this->mod_video_embed_model->keywords = Req::Post_Safe('keywords');
            $this->mod_video_embed_model->description = Req::Post_Safe('description');
            $this->mod_video_embed_model->acl_id = Req::Post_Safe('acl_id');
            if (isset($this->mod_video_embed_model->user_id)) {
                unset($this->mod_video_embed_model->user_id);
            }

            // Eğer Resim Varsa
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_video_embed';
                $image->name = 'image';
                $option = [
                    '/' =>
                    [
                        'width' => 225,
                        'height' => NULL,
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
                    $new_image_url = $image->url;
                    // Berî barkirina wêneyê nû, yê kevin jê bibin
                    $old_data = Load::model('mod_video_embed');
                    $old_data->where_id($id)->gRo()->sync();
                    if ($old_data->image != '') {
                        $image->mod = 'mod_video_embed';
                        $image->url = $old_data->image;
                        $image->_delete_files($option);
                    }
                    $this->mod_video_embed_model->image = $new_image_url;
                } else {
                    $this->_error_message($image->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_video_embed_model->image)) {
                    unset($this->mod_video_embed_model->image);
                }
            }

            $this->mod_video_embed_model->id = $id;
            if ($this->mod_video_embed_model->update('id')) {
                $this->_oke_message($this->lang['mod_video_embed']['message']['oke']['edit']);
            } else {
                // Eğer Resim varsa
                if (isset($image->url)) {
                    $image->_rollback();
                }
                $this->_error_message($this->lang['mod_video_embed']['message']['error']['edit']);
            }
        }
    }



    public function delete()
    {
        // Listeleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_video_embed',  'delete', $this->lang['mod_video_embed']['role_errors'][ 'delete']) === False) {
            return False;
        }
        if (Req::is_Post('delete')) {
            if ($this->_delete(Req::Post('delete'))) {
                echo 'Oke';
            } else {
                echo 'HATA';
            }
        } else {
            exit($this->lang['mod_video_embed']['errors']['record']);
        }
    }

    public function _delete($delete)
    {
        $this->mod_video_embed_model = Load::model('mod_video_embed');
        foreach ($delete as $s) {
            $this->mod_video_embed_model->flush();
            $this->mod_video_embed_model->where_id($s)->gRo();
            if ($this->mod_video_embed_model->getSize() === 0) {
                exit($this->lang['mod_video_embed']['errors']['record']);
            }
            $this->mod_video_embed_model->sync();
            // Eğer Eklenen İçerik Başkasınınsa Yönetme Permissionsi Varmı ?
            if ($this->user->checkPermission('mod_video_embed', 'moderate_delete', $this->mod_video_embed_model->user_id, $this->lang['mod_video_embed']['role_errors']['moderate_delete']) === False) {
                return False;
            }

            // Modül varmı ve Gallery Ekleniyormu
            $this->mod = Load::model('mod_gallery_mod');
            if ($this->mod->exists_mod_name("mod_video_embed") === TRUE) {
                // İçeriğe Ait Gallery Varsa Silelim
                Run::import('mod_gallery', '_remove', ['mod_video_embed', $this->mod_video_embed_model->id]);
            }
            // Modül varmı ve Dosya Ekleniyormu
            $this->mod = Load::model('mod_attachment_mod');
            if ($this->mod->exists_mod_name("mod_video_embed") === TRUE) {
                // İçeriğe Ait Dosya Varsa Silelim
                Run::import('mod_attachment', '_remove', ['mod_video_embed', $this->mod_video_embed_model->id]);
            }

            if ($this->mod_video_embed_model->image != '') {
                $image = Load::mod('image');
                $image->mod = 'mod_video_embed';
                $image->name = 'image';
                $image->url = $this->mod_video_embed_model->image;

                $option = [
                    '/' =>
                    [
                        'width' => 225,
                        'height' => NULL,
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


            $this->mod_video_embed_model->delete();
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


        $i = Load::model('mod_video_embed');


        $colomns = [];
        $colomns[] = 'id';
        $colomns[] = 'name';
        $colomns[] = 'image';
        $colomns[] = 'duration';
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
            (Roll::hasPermission('mod_video_embed', 'moderate_edit') !== TRUE)
            and
            (Roll::hasPermission('mod_video_embed', 'moderate_delete') !== TRUE)
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


            'y' => ['success', $this->lang['mod_video_embed']['admin']['radio']['publish_yes']],




            'n' => ['danger', $this->lang['mod_video_embed']['admin']['radio']['publish_no']],



        ];









        if ($getCount > 0) {
            foreach ($i->toArray() as $content) {

                $last = "";
                if (Roll::hasPermission('mod_video_embed', 'edit')) {
                    $last .= '<button onclick="window.location.href=\''. $this->site['sadres'] .'mod_video_embed/edit/' . $content['id'] . '\';return false;" class="btn default btn-xs purple">
    <i class="fa fa-edit"></i> ' . $this->lang['mod_video_embed']['button']['edit'] . ' </button>';
                }
                if (Roll::hasPermission('mod_video_embed',  'delete')) {
                    $last .= '<button onclick="mod_video_embed_table_ajax.akm.processDelete(\''. $content['id'] .'\'); return false;" class="btn default btn-xs red">
    <i class="fa fa-trash-o"></i> ' . $this->lang['mod_video_embed']['button'][ 'delete'] . ' </button>';
                }

                if (Roll::hasPermission('mod_video_embed', 'gallery')) {
                    $last .= '<button onclick="mod_video_embed_table_ajax.akm.processGallery(\''. $content['id'] .'\'); return false;" class="btn default btn-xs blue">
    <i class="fa fa-file-image-o"></i> ' . $this->lang['mod_video_embed']['button']['gallery'] . ' </button>';
                }

                if (Roll::hasPermission('mod_video_embed', 'file')) {
                    $last .= '<button onclick="mod_video_embed_table_ajax.akm.processDosya(\''. $content['id'] .'\'); return false;" class="btn default btn-xs green">
    <i class="fa fa-file-zip-o"></i> ' . $this->lang['mod_video_embed']['button']['file'] . ' </button>';
                }
                $data[] = [
                    '<input type="checkbox" name="id[]" value="'. $content['id'] .'">',
                    $content['id'],
                    $content['name'],
                    '<img src="' . $this->site['radres'] . 'mod_video_embed/thumb/' . $content['image'] . '?v=' . time() . '">',
                    $content['duration'],
                    $content['lang'],
                    $content['created_at'],
                    '<span class="label label-' . $status_list[$content['status']]['0'] . '">' . $status_list[$content['status']]['1'] . '</span>',
                    $last
                ];
            }
        }
        $ii = Load::model('mod_video_embed');
        echo json_encode([
            'data' => $data,
            'recordsFiltered' => $getCount,
            'recordsTotal' => $ii->getCount(),
        ]);
    }




    // publish Sayfası
    public function publish()
    {
        // duzenle Permissionsi varmı?
        if ($this->user->hasPermission('mod_video_embed', 'edit', $this->lang['mod_video_embed']['role_errors']['edit']) === False) {
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
        // mod_video_embed Modeli Yükleyelim
        $this->mod_video_embed_model = Load::model('mod_video_embed');
        if (is_array($query)) {
            foreach ($query as $s) {
                $this->mod_video_embed_model->id = $s;
                $this->mod_video_embed_model->status = 'y';
                if ($this->mod_video_embed_model->update('id')) {
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
        if ($this->user->hasPermission('mod_video_embed', 'edit', $this->lang['mod_video_embed']['role_errors']['edit']) === False) {
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
        // mod_video_embed Modeli Yükleyelim
        $this->mod_video_embed_model = Load::model('mod_video_embed');
        if (is_array($query)) {
            foreach ($query as $s) {
                $this->mod_video_embed_model->id = $s;
                $this->mod_video_embed_model->status = 'n';
                if ($this->mod_video_embed_model->update('id')) {
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
        $this->tema->veri('hata', True);
        $this->tema->veri('hatalar', [$mesaj]);
    }

    // Error Mesaj
    private function _error($mesaj)
    {
        $this->tema->veri('error', TRUE);
        $this->tema->veri('mesaj', $mesaj);
    }

    // Check if request is AJAX (for wizard and other AJAX calls)
    private function _isAjaxRequest()
    {
        // Rêya herî hêsan: POST parameter kontrol bike
        if (Req::is_Post('ajax_request') && Req::Post_Safe('ajax_request') === '1') {
            return true;
        }

        // Fallback: Header kontrol bike
        return (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );
    }
}
