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
        header("Location: " . $this->site['saddress'] . 'mod_slider/list');
        exit;
    }

    // Düzenleme Sayfası
    public function edit($id = NULL)
    {
        header("Location: " . $this->site['saddress'] . 'mod_slider/list');
        exit;
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
            $this->mod_slider_model->user_id = $this->session->get('id');

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
        exit($this->lang['mod_slider']['errors']['record']);
    }

    public function _delete($sil)
    {
        return TRUE;
    }

    public function _valid_date($data = '')
    {
        if ($data == '') {
            return false;
        }
        return preg_match('@(\d{4})-(\d{2})-(\d{2})$@', $data);
    }

    private function _hasColumn($table, $column)
    {
        $db = Load::lib('Database');
        if ($db->getAdapterType() && strpos($db->getAdapterType(), 'PDO_SQLITE') !== false) {
            $db->fetch_array("PRAGMA table_info('" . addslashes($table) . "')");
            $result = $db->toArray();
            if (is_array($result)) {
                foreach ($result as $row) {
                    if (isset($row['name']) && $row['name'] === $column) {
                        return true;
                    }
                }
            }
            return false;
        }

        $db->fetch_array("SHOW COLUMNS FROM `" . $table . "` LIKE '" . addslashes($column) . "'");
        $result = $db->getResult()->toArray();
        return is_array($result) && !empty($result);
    }

    private function _tableExists($table)
    {
        $db = Load::lib('Database');
        if ($db->getAdapterType() && strpos($db->getAdapterType(), 'PDO_SQLITE') !== false) {
            $db->fetch_array("SELECT name FROM sqlite_master WHERE type='table' AND name='" . addslashes($table) . "'");
            $result = $db->toArray();
            return !empty($result);
        }

        $db->fetch_array("SHOW TABLES LIKE '" . addslashes($table) . "'");
        $result = $db->getResult()->toArray();
        return !empty($result);
    }

    public function datatable_json()
    {
        $user_id_filter = "";
        if (
            (Roll::hasPermission('mod_slider', 'moderate_edit') !== TRUE)
            and
            (Roll::hasPermission('mod_slider', 'moderate_delete') !== TRUE)
        ) {
            $user_id_filter = " AND user_id='" . intval($this->session->get('id')) . "'";
        }

        $db = Load::lib('Database');

        $queries = [];
        
        // 1. mod_news
        if ($this->_tableExists('mod_news') && $this->_hasColumn('mod_news', 'slider')) {
            $queries[] = [
                'table' => 'mod_news',
                'select' => "SELECT id, name, image, lang, created_at, 'mod_news' AS module FROM mod_news WHERE slider = 'y' AND status = 'y'" . $user_id_filter,
                'count' => "SELECT COUNT(*) as total FROM mod_news WHERE slider = 'y' AND status = 'y'" . $user_id_filter
            ];
        }
        
        // 2. mod_publication
        if ($this->_tableExists('mod_publication') && $this->_hasColumn('mod_publication', 'slider')) {
            $queries[] = [
                'table' => 'mod_publication',
                'select' => "SELECT id, name, image, lang, created_at, 'mod_publication' AS module FROM mod_publication WHERE slider = 'y' AND status = 'y'" . $user_id_filter,
                'count' => "SELECT COUNT(*) as total FROM mod_publication WHERE slider = 'y' AND status = 'y'" . $user_id_filter
            ];
        }
        
        // 3. mod_content
        if ($this->_tableExists('mod_content') && $this->_hasColumn('mod_content', 'slider')) {
            $queries[] = [
                'table' => 'mod_content',
                'select' => "SELECT id, name, image, lang, created_at, 'mod_content' AS module FROM mod_content WHERE slider = 'y' AND status = 'y'" . $user_id_filter,
                'count' => "SELECT COUNT(*) as total FROM mod_content WHERE slider = 'y' AND status = 'y'" . $user_id_filter
            ];
        }
        
        // 4. mod_product
        if ($this->_tableExists('mod_product') && $this->_hasColumn('mod_product', 'show_on_homepage')) {
            $queries[] = [
                'table' => 'mod_product',
                'select' => "SELECT id, name, image, lang, created_at, 'mod_product' AS module FROM mod_product WHERE show_on_homepage = 'y' AND status = 'y'" . $user_id_filter,
                'count' => "SELECT COUNT(*) as total FROM mod_product WHERE show_on_homepage = 'y' AND status = 'y'" . $user_id_filter
            ];
        }

        if (empty($queries)) {
            echo json_encode([
                'data' => [],
                'recordsFiltered' => 0,
                'recordsTotal' => 0
            ]);
            return;
        }

        // Count query
        $count_selects = [];
        foreach ($queries as $q) {
            $count_selects[] = $q['count'];
        }
        $count_query = "SELECT SUM(total) as total_count FROM (" . implode(" UNION ALL ", $count_selects) . ") t";
        $db->fetch_array($count_query);
        $countRow = $db->toArray();
        $totalRecords = isset($countRow[0]['total_count']) ? intval($countRow[0]['total_count']) : 0;

        $limit = 10;
        $offset = 0;
        if (Req::is_Post('length') && intval(Req::Post_Safe('length'))) {
            $limit = intval(Req::Post_Safe('length'));
        }
        if (Req::is_Post('start') && intval(Req::Post_Safe('start'))) {
            $offset = intval(Req::Post_Safe('start'));
        }

        // Fetch query
        $select_queries = [];
        foreach ($queries as $q) {
            $select_queries[] = $q['select'];
        }
        $fetch_query = implode(" UNION ALL ", $select_queries) . " ORDER BY created_at DESC, id DESC LIMIT " . intval($limit) . " OFFSET " . intval($offset);
        $db->fetch_array($fetch_query);
        $rows = $db->toArray();

        $module_labels = [
            'mod_news' => isset($this->lang['mod_slider']['modules']['news']) ? $this->lang['mod_slider']['modules']['news'] : 'Nûçe',
            'mod_publication' => isset($this->lang['mod_slider']['modules']['publication']) ? $this->lang['mod_slider']['modules']['publication'] : 'Weşan',
            'mod_content' => isset($this->lang['mod_slider']['modules']['content']) ? $this->lang['mod_slider']['modules']['content'] : 'Naverok',
            'mod_product' => isset($this->lang['mod_slider']['modules']['product']) ? $this->lang['mod_slider']['modules']['product'] : 'Hilber',
        ];

        $data = [];
        if ($totalRecords > 0 && is_array($rows)) {
            foreach ($rows as $content) {
                $compound_id = $content['module'] . ':' . $content['id'];
                
                $thumb_url = '';
                if (!empty($content['image'])) {
                    $thumb_url = $this->site['iaddress'] . $content['module'] . '/thumb/' . $content['image'];
                }
                
                $module_label = isset($module_labels[$content['module']]) ? $module_labels[$content['module']] : $content['module'];
                
                $data[] = [
                    '',
                    $compound_id,
                    $content['name'],
                    '<img src="' . $thumb_url . '">',
                    $content['lang'],
                    $content['created_at'],
                    $module_label
                ];
            }
        }

        echo json_encode([
            'data' => $data,
            'recordsFiltered' => $totalRecords,
            'recordsTotal' => $totalRecords
        ]);
    }

    // publish Sayfası
    public function publish()
    {
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
        if (is_array($query)) {
            $db = Load::lib('Database');
            foreach ($query as $s) {
                $parts = explode(':', $s);
                if (count($parts) === 2) {
                    $module = Req::Clear($parts[0]);
                    $id = intval($parts[1]);
                    if ($module === 'mod_product' && $id > 0 && $this->_tableExists('mod_product') && $this->_hasColumn('mod_product', 'show_on_homepage')) {
                        $db->query("UPDATE `mod_product` SET show_on_homepage = 'y' WHERE id = " . $id);
                    } elseif (in_array($module, ['mod_news', 'mod_publication', 'mod_content']) && $id > 0 && $this->_tableExists($module) && $this->_hasColumn($module, 'slider')) {
                        $db->query("UPDATE `" . $module . "` SET slider = 'y' WHERE id = " . $id);
                    }
                }
            }
            return TRUE;
        }
        return FALSE;
    }

    // unpublish Sayfası
    public function unpublish()
    {
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
        if (is_array($query)) {
            $db = Load::lib('Database');
            foreach ($query as $s) {
                $parts = explode(':', $s);
                if (count($parts) === 2) {
                    $module = Req::Clear($parts[0]);
                    $id = intval($parts[1]);
                    if ($module === 'mod_product' && $id > 0 && $this->_tableExists('mod_product') && $this->_hasColumn('mod_product', 'show_on_homepage')) {
                        $db->query("UPDATE `mod_product` SET show_on_homepage = 'n' WHERE id = " . $id);
                    } elseif (in_array($module, ['mod_news', 'mod_publication', 'mod_content']) && $id > 0 && $this->_tableExists($module) && $this->_hasColumn($module, 'slider')) {
                        $db->query("UPDATE `" . $module . "` SET slider = 'n' WHERE id = " . $id);
                    }
                }
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
