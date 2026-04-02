<?php
/* AKM Software.
 * @2015
 * mod_document Module Page
 * mod/mod_document/index.php
 */
class mod_document_mod extends indexMain
{
    public $mod_document_model;
    function __construct()
    {
        parent::__construct();
        // Call the Theme Library
        $this->tema = Load::lib('AdminTemplate');
        // Load the Database Library
        $this->db = Load::lib('Database');
        // Get site settings
        //$this->site = Kayit::getir('site_config');
        // Call the Language File
        $this->lang = Load::lang('mod_document');
        // Send the Language File to the Theme
        $this->tema->lang($this->lang);
        // Session Library
        $this->session = Load::lib('Session');
        // User Model
        $this->user = Load::model('mod_user.user');
    }
    // Add Page
    public function add()
    {
        // Is there Add Permission?
        if ($this->user->hasPermission('mod_document', 'add', $this->lang['mod_document']['role_errors']['add'], 1) === False) {
            return False;
        }
        if (Req::is_Post('add')) {
            $this->_add();
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_document']['add'];
        $this->tema->tpl('mod_document/ekle');
        $this->tema->veri('site', $this->site);
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY seo DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $mod_document_nested = Load::model('mod_menu');
        $mod_document_nested->order('order', 'ASC')->all();
        $select_nested_menu_result = Helper::nested($mod_document_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_menu_result', $select_nested_menu_result);
    }

    // Edit Page
    public function edit($id = NULL)
    {
        // Is there Edit Permission?
        if ($this->user->hasPermission('mod_document', 'edit', $this->lang['mod_document']['role_errors']['edit'], 1) === FALSE) {
            return FALSE;
        }
        $this->mod_document_model = Load::model('mod_document');
        $id = Req::Url_Clear($id);
        if ($this->mod_document_model->exists_id($id) === False) {
            $this->_error($this->lang['mod_document']['errors']['record']);
            Run::import('management', '_index');
            $this->tema->tpl('mod_document/duzenle');
            return;
        }

        $d = $this->mod_document_model->getResultOne()->toArray();

        // If the added content belongs to someone else, is there management permission?
        if ($this->user->checkPermission('mod_document', 'moderate_edit', $d['user_id'], $this->lang['mod_document']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id);
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_document']['edit'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        $this->tema->tpl('mod_document/duzenle');
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY seo DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
    }

    // List Page
    public function list()
    {
        // Is there List Permission?
        if ($this->user->hasPermission('mod_document', 'list', $this->lang['mod_document']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_document']['list'];
        $this->tema->tpl('mod_document/list');
        $this->tema->veri('site', $this->site);
    }
    public function image_remove_image()
    {
        // Düzenleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_document', 'edit', $this->lang['mod_document']['role_errors']['edit'], 1) === FALSE) {
            return FALSE;
        }

        $id = intval(Req::Post_Safe('id'));

        $this->mod_document_model = Load::model('mod_document');
        $this->mod_document_model->where_id($id)->gRo();
        if ($this->mod_document_model->getSize() === 0) {
            echo $this->lang['mod_document']['errors']['record'];
            return FALSE;
        }


        $this->mod_document_model->sync();
        // If the added content belongs to someone else, is there management permission?
        if ($this->user->checkPermission('mod_document', 'moderate_edit', $this->mod_document_model->user_id, $this->lang['mod_document']['role_errors']['moderate_edit']) === False) {
            return False;
        }

        if ($this->mod_document_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_document';
            $image->url = $this->mod_document_model->image;
            $option = [
                '/' =>
                [
                    'width' => 200,
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
                $this->mod_document_model->image = '';
                $this->mod_document_model->update('id');
                echo 'Oke';
            } else {
                echo $image->error;
                return FALSE;
            }
        } else {
            echo 'Oke';
        }
    }

    // Add Function
    private function _add()
    {
        $this->val = Load::validator('mod_document.ekle');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_document_model = Load::model('mod_document');

            $this->mod_document_model->lang = Req::Post_Safe('lang');
            $this->mod_document_model->name = Req::Post_Safe('name');









            $this->mod_document_model->seo = Req::Post_Safe('seo');
            $this->mod_document_model->status = Req::Post_Safe('status');

            $this->mod_document_model->title = Req::Post_Safe('title');
            $this->mod_document_model->keywords = Req::Post_Safe('keywords');
            $this->mod_document_model->description = Req::Post_Safe('description');
            $this->mod_document_model->user_id = $this->session->getir('id');

            // If Image exists
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_document';
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
                        'width' => 100,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                ];
                if ($image->_resize($option)) {
                    $this->mod_document_model->image = $image->url;
                } else {
                    $this->_error_message($image->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_document_model->image)) {
                    unset($this->mod_document_model->image);
                }
            }

            if ($this->mod_document_model->save(TRUE)) {
                // If it was requested to be added to the menu
                if (Req::Post_Safe('menu') != 0) {
                    $this->model = Load::model('mod_menu');
                    $this->model->parent_id = Req::Post_Safe('menu');
                    $this->model->name = $this->mod_document_model->name;
                    $this->model->lang = $this->mod_document_model->lang;
                    $this->model->url = $this->mod_document_model->seo;
                    $this->model->status = $this->mod_document_model->status;
                    $this->model->url = $this->mod_document_model->lang . '/dosyalar/' . $this->model->url;
                    $this->model->save();
                }
                $this->_oke_message($this->lang['mod_document']['message']['oke']['add']);
            } else {
                // If Image exists, rollback
                if (isset($image->url)) {
                    $image->_rollback();
                }

                $this->_error_message($this->lang['mod_document']['message']['oke']['add']);
            }
        }
    }

    // Edit Function
    private function _edit($id = NULL)
    {
        $this->val = Load::validator('mod_document.duzenle');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_document_model = Load::model('mod_document');

            $this->mod_document_model->id = $id;
            $this->mod_document_model->lang = Req::Post_Safe('lang');
            $this->mod_document_model->name = Req::Post_Safe('name');





            $this->mod_document_model->seo = Req::Post_Safe('seo');
            $this->mod_document_model->status = Req::Post_Safe('status');

            $this->mod_document_model->title = Req::Post_Safe('title');
            $this->mod_document_model->keywords = Req::Post_Safe('keywords');
            $this->mod_document_model->description = Req::Post_Safe('description');
            // If this content was previously sent to the menu, let's update it
            $new_mod_document = new mod_document_model();
            $new_mod_document->where_id($id)->gRo()->sync();
            $url = $new_mod_document->lang . '/dosyalar/' . $new_mod_document->seo;

            if (isset($this->mod_document_model->user_id)) {
                unset($this->mod_document_model->user_id);
            }

            // If Image exists
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_document';
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
                        'width' => 100,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                ];
                if ($image->_resize($option)) {
                    $this->mod_document_model->image = $image->url;
                } else {
                    $this->_error_message($image->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_document_model->image)) {
                    unset($this->mod_document_model->image);
                }
            }

            $this->mod_document_model->id = $id;
            if ($this->mod_document_model->update('id')) {
                // If this content was previously sent to the menu, let's update it
                $mod_menu_model = Load::model('mod_menu');
                if ($mod_menu_model->exists_url($url)) {
                    $mod_menu_model->getResultOne()->sync();
                    $mod_menu_model->name = $this->mod_document_model->name;
                    $mod_menu_model->lang = $this->mod_document_model->lang;
                    $mod_menu_model->url = $this->mod_document_model->seo;
                    $mod_menu_model->status = $this->mod_document_model->status;
                    $mod_menu_model->url = $this->mod_document_model->lang . '/dosyalar/' . $mod_menu_model->url;
                    $mod_menu_model->update();
                }


                $this->_oke_message($this->lang['mod_document']['message']['oke']['edit']);
            } else {
                // If Image exists, rollback
                if (isset($image->url)) {
                    $image->_rollback();
                }
                $this->_error_message($this->lang['mod_document']['message']['error']['edit']);
            }
        }
    }


    function sil()
    {
        // Is there delete permission?
        if ($this->user->hasPermission('mod_document',  'delete', $this->lang['mod_document']['role_errors'][ 'delete']) === False) {
            return False;
        }
        if (Req::is_Post( 'delete')) {
            if ($this->_delete(Req::Post_Safe( 'delete'))) {
                echo 'Oke';
            } else {
                echo 'HATA';
            }
        } else {
            exit($this->lang['mod_document']['errors']['record']);
        }
    }

    public function _delete($sil)
    {
        $this->mod_document_model = Load::model('mod_document');
        foreach ($sil as $s) {
            $this->mod_document_model->flush();
            $this->mod_document_model->where_id($s)->gRo();
            if ($this->mod_document_model->getSize() === 0) {
                exit($this->lang['mod_document']['errors']['record']);
            }
            $this->mod_document_model->sync();
            // If the added content belongs to someone else, is there management permission?
            if ($this->user->checkPermission('mod_document', 'moderate_delete', $this->mod_document_model->user_id, $this->lang['mod_document']['role_errors']['moderate_delete']) === False) {
                return False;
            }

            // Check if module exists and Gallery is being added
            $this->mod = Load::model('mod_gallery_mod');
            if ($this->mod->exists_mod_name("mod_document") === TRUE) {
                // If there is Gallery belonging to the content, let's delete it
                Run::import('mod_gallery', '_remove', ['mod_document', $this->mod_document_model->id]);
            }
            // Check if module exists and File is being added
            $this->mod = Load::model('mod_attachment_mod');
            if ($this->mod->exists_mod_name("mod_document") === TRUE) {
                // If there is File belonging to the content, let's delete it
                Run::import('mod_attachment', '_remove', ['mod_document', $this->mod_document_model->id]);
            }

            if ($this->mod_document_model->image != '') {
                $image = Load::mod('image');
                $image->mod = 'mod_document';
                $image->name = 'image';
                $image->url = $this->mod_document_model->image;
                $option = [
                    '/' =>
                    [
                        'width' => 200,
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


            $this->mod_document_model->delete();
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


        $i = Load::model('mod_document');


        $colomns = [];
        $colomns[] = 'id';
        $colomns[] = 'name';
        $colomns[] = 'image';
        $colomns[] = 'lang';
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
            (Roll::hasPermission('mod_document', 'moderate_edit') !== TRUE)
            and
            (Roll::hasPermission('mod_document', 'moderate_delete') !== TRUE)
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


            'y' => ['success', $this->lang['mod_document']['admin']['radio']['publish_yes']],




            'n' => ['danger', $this->lang['mod_document']['admin']['radio']['publish_no']],



        ];















        if ($getCount > 0) {
            foreach ($i->toArray() as $content) {

                $last = "";
                if (Roll::hasPermission('mod_document', 'edit')) {
                    $last .= '<button onclick="mod_document_table_ajax.akm.processEdit(' . $content['id'] . ');return false;" class="btn default btn-xs purple">
    <i class="fa fa-edit"></i> ' . $this->lang['mod_document']['button']['edit'] . ' </button>';
                }
                if (Roll::hasPermission('mod_document',  'delete')) {
                    $last .= '<button onclick="mod_document_table_ajax.akm.processSil(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs red">
    <i class="fa fa-trash-o"></i> ' . $this->lang['mod_document']['button'][ 'delete'] . ' </button>';
                }

                if (Roll::hasPermission('mod_document', 'gallery')) {
                    $last .= '<button onclick="mod_document_table_ajax.akm.processGallery(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs blue">
    <i class="fa fa-file-image-o"></i> ' . $this->lang['mod_document']['button']['gallery'] . ' </button>';
                }

                if (Roll::hasPermission('mod_document', 'file')) {
                    $last .= '<button onclick="mod_document_table_ajax.akm.processDosya(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs green">
    <i class="fa fa-file-zip-o"></i> ' . $this->lang['mod_document']['button']['file'] . ' </button>';
                }
                $data[] = [
                    '<input type="checkbox" name="id[]" value="' . $content['id'] . '">',
                    $content['id'],
                    $content['name'],
                    '<img src="' . $this->site['radres'] . 'mod_document/thumb/' . $content['image'] . '">',
                    $content['lang'],
                    '<span class="label label-' . $status_list[$content['status']]['0'] . '">' . $status_list[$content['status']]['1'] . '</span>',
                    $last
                ];
            }
        }
        $ii = new mod_document_model();
        echo json_encode([
            'data' => $data,
            'recordsFiltered' => $getCount,
            'recordsTotal' => $ii->getCount(),
            // 'customActionStatus'=>'OK',
            ///'customActionMessage'=>'Oldumu Şimdi?'
        ]);
    }





    // Success Message
    private function _oke_message($mesaj)
    {
        $this->tema->veri('oke', True);
        $this->tema->veri('mesaj', $mesaj);
    }

    // Error Message
    private function _error_message($mesaj)
    {
        $this->tema->veri('hata', True);
        $this->tema->veri('hatalar', [$mesaj]);
    }

    // Error Message
    private function _error($mesaj)
    {
        $this->tema->veri('error', TRUE);
        $this->tema->veri('mesaj', $mesaj);
    }
}
