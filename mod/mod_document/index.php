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
        $this->tema->tpl('mod_document/add');
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
            $this->tema->tpl('mod_document/edit');
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
        $this->tema->tpl('mod_document/edit');
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
        $totalRow = $this->db->query("SELECT COUNT(*) AS total_count FROM mod_document")->fetch(PDO::FETCH_ASSOC);
        $liveRow = $this->db->query("SELECT COUNT(*) AS live_count FROM mod_document WHERE status='y'")->fetch(PDO::FETCH_ASSOC);
        $this->tema->veri('document_stats', [
            'total' => intval($totalRow['total_count'] ?? 0),
            'live' => intval($liveRow['live_count'] ?? 0),
        ]);
        $this->tema->veri('site', $this->site);
        $this->tema->tpl('mod_document/list');
    }
    public function image_remove_image()
    {
        // Check edit permission.
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
        $val = Load::validator('mod_document.add');
        if ($val->check()) {
            $this->tema->veri('errors', $val->errors);
            return;
        } else {
            $this->mod_document_model = Load::model('mod_document');

            $this->mod_document_model->lang = Req::Post_Safe('lang');
            $this->mod_document_model->name = Req::Post_Safe('name');

            $this->mod_document_model->seo = Req::Post_Safe('seo');
            $this->mod_document_model->status = Req::Post_Safe('status');

            $this->mod_document_model->title = Req::Post_Safe('title');
            $this->mod_document_model->keywords = Req::Post_Safe('keywords');
            $this->mod_document_model->description = Req::Post_Safe('description');
            $this->mod_document_model->user_id = $this->session->get('id');

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
                $this->_success_message($this->lang['mod_document']['message']['success']['add']);
            } else {
                // If Image exists, rollback
                if (isset($image->url)) {
                    $image->_rollback();
                }

                $this->_error_message($this->lang['mod_document']['message']['error']['add']);
            }
        }
    }

    // Edit Function
    private function _edit($id = NULL)
    {
        $val = Load::validator('mod_document.edit');
        if ($val->check($id)) {
            $this->tema->veri('errors', $val->errors);
            return;
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


                $this->_success_message($this->lang['mod_document']['message']['success']['edit']);
            } else {
                // If Image exists, rollback
                if (isset($image->url)) {
                    $image->_rollback();
                }
                $this->_error_message($this->lang['mod_document']['message']['error']['edit']);
            }
        }
    }


    public function delete()
    {
        // Is there delete permission?
        if ($this->user->hasPermission('mod_document',  'delete', $this->lang['mod_document']['role_errors'][ 'delete']) === False) {
            return False;
        }

        $query = Req::Post_Safe('query');
        if (!is_array($query) || empty($query)) {
            echo 'ERROR';
            return false;
        }

        if ($this->_delete($query)) {
            echo 'Oke';
            return true;
        }

        echo 'ERROR';
        return false;
    }

    public function _delete($ids)
    {
        $this->mod_document_model = Load::model('mod_document');
        foreach ($ids as $id) {
            $id = intval($id);
            $this->mod_document_model->flush();
            $this->mod_document_model->where_id($id)->gRo();
            if ($this->mod_document_model->getSize() === 0) {
                continue;
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
        if ($this->user->hasPermission('mod_document', 'list', $this->lang['mod_document']['role_errors']['list'], 1) === false) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'draw' => intval(Req::Post_Safe('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
            return false;
        }

        $limit = 10;
        $offset = 0;
        $draw = intval(Req::Post_Safe('draw'));

        if (Req::is_Post('length') && intval(Req::Post_Safe('length')) > 0) {
            $limit = intval(Req::Post_Safe('length'));
        }

        if (Req::is_Post('start') && intval(Req::Post_Safe('start')) >= 0) {
            $offset = intval(Req::Post_Safe('start'));
        }

        $whereClauses = [];
        if (
            Roll::hasPermission('mod_document', 'moderate_edit') !== true
            && Roll::hasPermission('mod_document', 'moderate_delete') !== true
        ) {
            $whereClauses[] = "d.user_id='" . intval($this->session->get('id')) . "'";
        }

        $search = Req::Post_Safe('search');
        if (is_array($search)) {
            $searchName = trim((string) ($search['name'] ?? ''));
            if ($searchName !== '') {
                $safeSearch = Req::Clear($searchName);
                $whereClauses[] = "(d.name LIKE '%" . $safeSearch . "%' OR d.title LIKE '%" . $safeSearch . "%' OR d.description LIKE '%" . $safeSearch . "%')";
            }

            $searchLang = trim((string) ($search['lang'] ?? ''));
            if ($searchLang !== '') {
                $whereClauses[] = "d.lang='" . Req::Clear($searchLang) . "'";
            }
        }

        $whereSql = '';
        if (!empty($whereClauses)) {
            $whereSql = ' WHERE ' . implode(' AND ', $whereClauses);
        }

        $totalRow = $this->db->query("SELECT COUNT(*) AS total_count FROM mod_document")->fetch(PDO::FETCH_ASSOC);
        $recordsTotal = intval($totalRow['total_count'] ?? 0);

        $countRow = $this->db->query("SELECT COUNT(*) AS filtered_count FROM mod_document d" . $whereSql)->fetch(PDO::FETCH_ASSOC);
        $recordsFiltered = intval($countRow['filtered_count'] ?? 0);

        $listSql = "
            SELECT
                d.id,
                d.lang,
                d.name,
                d.status,
                d.image,
                CASE WHEN EXISTS (
                    SELECT 1 FROM mod_gallery g
                    WHERE g.mod_name='mod_document' AND g.vid=d.id
                ) THEN 1 ELSE 0 END AS has_gallery,
                CASE WHEN EXISTS (
                    SELECT 1 FROM mod_attachment a
                    WHERE a.mod_name='mod_document' AND a.vid=d.id
                ) THEN 1 ELSE 0 END AS has_file
            FROM mod_document d
            " . $whereSql . "
            ORDER BY d.id DESC
            LIMIT " . intval($limit) . " OFFSET " . intval($offset);

        $records = $this->db->query($listSql)->fetchAll(PDO::FETCH_ASSOC);
        $data = [];

        if (is_array($records)) {
            foreach ($records as $row) {
                $image = trim((string) ($row['image'] ?? ''));
                $data[] = [
                    'id' => intval($row['id']),
                    'lang' => (string) ($row['lang'] ?? ''),
                    'name' => html_entity_decode((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8'),
                    'status' => (string) ($row['status'] ?? 'n'),
                    'image_url' => $image !== '' ? $this->site['iaddress'] . 'mod_document/thumb/' . $image : '',
                    'has_gallery' => intval($row['has_gallery']) === 1,
                    'has_file' => intval($row['has_file']) === 1,
                ];
            }
        }

        if (ob_get_level() > 0) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
        return true;
    }





    // Success Message
    private function _success_message($message)
    {
        $this->tema->veri('success', True);
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
