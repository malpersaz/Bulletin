<?php
/* AKM Software.
 * @2015
 * mod_video_embed Module Page
 * mod/mod_video_embed/index.php
 */
class mod_video_embed_mod extends BaseModuleController
{
    public $mod_video_embed_model;
    private const VIDEO_IMAGE_WIDTH = 1280;
    private const VIDEO_THUMB_WIDTH = 640;

    protected $has_gallery = true;
    protected $has_files = true;

    protected $need_simple_data = [
        'lang' => 'lang_lang',
        'mod_acl' => 'mod_acl_acl_id'
    ];

    protected $image_fields = [];

    public function __construct()
    {
        parent::__construct('mod_video_embed');
        $this->ensureSliderColumn();
    }

    private function ensureSliderColumn()
    {
        $table = 'mod_video_embed';
        $column = 'slider';
        $safeTable = addslashes($table);
        $safeColumn = addslashes($column);
        $adapter = (string) $this->db->getAdapterType();
        $exists = false;

        if (strpos($adapter, 'PDO_SQLITE') !== false) {
            $this->db->fetch_array("PRAGMA table_info('" . $safeTable . "')");
            $rows = $this->db->toArray();
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    if (isset($row['name']) && $row['name'] === $column) {
                        $exists = true;
                        break;
                    }
                }
            }
        } elseif (strpos($adapter, 'POSTGRESQL') !== false) {
            $sql = "SELECT column_name FROM information_schema.columns WHERE table_schema NOT IN ('pg_catalog', 'information_schema') AND table_name='" . $safeTable . "' AND column_name='" . $safeColumn . "'";
            $this->db->fetch_array($sql);
            $exists = ($this->db->getSize() !== 0);
        } else {
            $this->db->fetch_array("SHOW COLUMNS FROM `" . $safeTable . "` LIKE '" . $safeColumn . "'");
            $exists = ($this->db->getSize() !== 0);
        }

        if (!$exists) {
            if (strpos($adapter, 'PDO_SQLITE') !== false) {
                $sql = "ALTER TABLE `" . $safeTable . "` ADD COLUMN slider TEXT CHECK(slider IN ('y', 'n')) NOT NULL DEFAULT 'n'";
            } elseif (strpos($adapter, 'POSTGRESQL') !== false) {
                $sql = "ALTER TABLE `" . $safeTable . "` ADD COLUMN slider VARCHAR(1) NOT NULL DEFAULT 'n'";
            } else {
                $sql = "ALTER TABLE `" . $safeTable . "` ADD COLUMN `slider` ENUM('y', 'n') NOT NULL DEFAULT 'n'";
            }
            $this->db->query($sql);
        }
    }

    protected function getDatatableColumns()
    {
        return [
            'id',
            'name|bold',
            'image|image',
            'lang|badge',
            'status|badge'
        ];
    }
    // Render the add form.
    public function add()
    {
        // Check add permission.
        if ($this->user->hasPermission('mod_video_embed', 'add', $this->lang['mod_video_embed']['role_errors']['add'], 1) === False) {
            return False;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->_add();
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_video_embed']['add'];
        $this->theme->tpl('mod_video_embed/add');
        $this->theme->set('site', $this->site);
        $this->db->fetch_array("SELECT * FROM `lang` WHERE status='y' ORDER BY is_default DESC, seo ASC");
        $this->theme->set('lang_lang', $this->db->getResult()->toArray());
        $mod_video_embed_category_nested = Load::model('mod_video_embed_category');
        $mod_video_embed_category_nested->order('order', 'ASC')->all();
        $select_nested_category_id_result = Helper::nested($mod_video_embed_category_nested->toArray(), 'parent_id', 'id');
        $this->theme->set('select_nested_category_id_result', $select_nested_category_id_result);
        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY id DESC");
        $this->theme->set('mod_acl_acl_id', $this->db->getResult()->toArray());
    }

    // Render the edit form.
    public function edit($id = NULL)
    {
        // Check edit permission.
        if ($this->user->hasPermission('mod_video_embed', 'edit', $this->lang['mod_video_embed']['role_errors']['edit'], 1) === False) {
            return False;
        }
        $this->mod_video_embed_model = Load::model('mod_video_embed');
        $id = Req::Url_Clear($id);
        if ($this->mod_video_embed_model->exists_id($id) === False) {
            $this->_error($this->lang['mod_video_embed']['errors']['record']);
            Run::import('management', '_index');
            $this->theme->tpl('mod_video_embed/edit');
            return;
        }

        $d = $this->mod_video_embed_model->getResultOne()->toArray();

        // Check moderate permission if content belongs to another user.
        if ($this->user->checkPermission('mod_video_embed', 'moderate_edit', $d['user_id'], $this->lang['mod_video_embed']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->_edit($id);
            $this->mod_video_embed_model->where_id($id)->gRo()->sync();
            $d = $this->mod_video_embed_model->toArray();
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_video_embed']['edit'];
        $this->theme->set('site', $this->site);
        $this->theme->set('d', $d);
        $this->theme->tpl('mod_video_embed/edit');
        $langWhere = "WHERE status='y'";
        if (!empty($d['lang'])) {
            $langWhere .= " OR seo='" . Req::Clear($d['lang']) . "'";
        }
        $this->db->fetch_array("SELECT * FROM `lang` " . $langWhere . " ORDER BY is_default DESC, seo ASC");
        $this->theme->set('lang_lang', $this->db->getResult()->toArray());
        $mod_video_embed_category_nested = Load::model('mod_video_embed_category');
        $mod_video_embed_category_nested->flush();
        $mod_video_embed_category_nested->order('order', 'ASC')->all();
        $select_nested_category_id_result = Helper::nested($mod_video_embed_category_nested->toArray(), 'parent_id', 'id');
        $this->theme->set('select_nested_category_id_result', $select_nested_category_id_result);
        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY id DESC");
        $this->theme->set('mod_acl_acl_id', $this->db->getResult()->toArray());
    }

    // Render the list page.
    public function list()
    {
        // Check list permission.
        if ($this->user->hasPermission('mod_video_embed', 'list', $this->lang['mod_video_embed']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_video_embed']['list'];
        $this->theme->tpl('mod_video_embed/list');
        $this->theme->set('site', $this->site);
    }

    public function autofill_metadata()
    {
        if ($this->user->hasPermission('mod_video_embed', 'add', '', 1) === False && $this->user->hasPermission('mod_video_embed', 'edit', '', 1) === False) {
            $this->_jsonResponse([
                'status' => 'error',
                'message' => $this->_autofillMessage('permission_denied'),
            ]);
            return;
        }

        $embed = trim((string) Req::Post('embed'));
        $original = trim((string) Req::Post('original'));
        $source = $embed !== '' ? $embed : $original;

        if ($source === '') {
            $this->_jsonResponse([
                'status' => 'error',
                'message' => $this->_autofillMessage('missing_source'),
            ]);
            return;
        }

        $videoId = $this->_extractYouTubeVideoId($source);
        if ($videoId === null) {
            $this->_jsonResponse([
                'status' => 'error',
                'message' => $this->_autofillMessage('invalid_source'),
            ]);
            return;
        }

        try {
            $metadata = $this->_fetchYouTubeMetadata($videoId);
            $imageFile = '';
            $imageUrl = '';

            if (!empty($metadata['thumbnail_url'])) {
                $imageFile = $this->_importRemoteThumbnail($metadata['thumbnail_url']);
                if ($imageFile !== '') {
                    $imageUrl = $this->site['iaddress'] . 'mod_video_embed/thumb/' . $imageFile . '?v=' . time();
                }
            }

            $this->_jsonResponse([
                'status' => 'success',
                'message' => $this->_autofillMessage('success'),
                'data' => [
                    'video_id' => $videoId,
                    'name' => $metadata['title'] ?? '',
                    'title' => $metadata['title'] ?? '',
                    'description' => $metadata['description'] ?? '',
                    'content' => $metadata['description'] ?? '',
                    'duration' => $metadata['duration'] ?? '',
                    'keywords' => $this->_makeKeywordString($metadata['title'] ?? ''),
                    'original' => 'https://www.youtube.com/watch?v=' . $videoId,
                    'embed' => $this->_buildEmbedCode($videoId),
                    'image' => $imageFile,
                    'image_url' => $imageUrl,
                    'source' => $metadata['source'] ?? 'youtube',
                    'duration_available' => !empty($metadata['duration']),
                ],
            ]);
        } catch (Throwable $e) {
            $message = $this->_autofillThrowableMessage($e);
            $this->_jsonResponse([
                'status' => 'error',
                'message' => $message,
            ]);
        }
    }

    // AJAX image upload from the list screen.
    public function ajax_image_upload()
    {
        // Check edit permission.
        if ($this->user->hasPermission('mod_video_embed', 'edit', $this->lang['mod_video_embed']['role_errors']['edit'], 1) === False) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video_embed']['ajax']['permission_denied']]);
            return;
        }

        $id = intval(Req::Post_Safe('id'));
        if ($id === 0) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video_embed']['ajax']['id_missing']]);
            return;
        }

        $this->mod_video_embed_model = Load::model('mod_video_embed');
        $this->mod_video_embed_model->where_id($id)->gRo();
        if ($this->mod_video_embed_model->getSize() === 0) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video_embed']['errors']['record']]);
            return;
        }
        $this->mod_video_embed_model->sync();

        // Check moderate permission if content belongs to another user.
        if ($this->user->checkPermission('mod_video_embed', 'moderate_edit', $this->mod_video_embed_model->user_id, $this->lang['mod_video_embed']['role_errors']['moderate_edit']) === False) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video_embed']['ajax']['content_permission_denied']]);
            return;
        }

        if (isset($_FILES['image']['name']) && ($_FILES['image']['name'] != '')) {
            $image = Load::mod('image');
            $image->mod = 'mod_video_embed';
            $image->name = 'image';
            $option = $this->_videoImageOptions();

            try {
                if ($image->_resize($option)) {
                    $new_image_url = $image->url;
                    // Delete the old image before saving the new one, if it exists.
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
                            'url' => $this->site['iaddress'] . 'mod_video_embed/thumb/' . $new_image_url . '?v=' . time()
                        ]);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video_embed']['ajax']['db_update_failed']]);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video_embed']['errors']['image_upload_failed']]);
                }
            } catch (Throwable $e) {
                echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video_embed']['errors']['image_upload_failed']]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video_embed']['ajax']['file_not_uploaded']]);
        }
    }

    public function image_remove_image()
    {
        // Check edit permission.
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
        // Check moderate permission if content belongs to another user.
        if ($this->user->checkPermission('mod_video_embed', 'moderate_edit', $this->mod_video_embed_model->user_id, $this->lang['mod_video_embed']['role_errors']['moderate_edit']) === False) {
            return False;
        }

        if ($this->mod_video_embed_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_video_embed';
            $image->url = $this->mod_video_embed_model->image;
            $option = $this->_videoImageOptions();
            if ($image->_delete_files($option)) {
                $this->mod_video_embed_model->image = '';
                $this->mod_video_embed_model->update('id');
                echo json_encode(['status' => 'success', 'message' => $this->lang['mod_video_embed']['remove_image_success']]);
            } else {
                echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video_embed']['errors']['image_delete_failed']]);
                return FALSE;
            }
        } else {
            echo json_encode(['status' => 'success', 'message' => $this->lang['mod_video_embed']['remove_image_success']]);
        }
    }

    // Handle add form submission.
    protected function _add()
    {
        $isAjax = $this->_isAjaxRequest();
        $autofillImage = Req::Post_Safe('autofill_image');
        $this->val = Load::validator('mod_video_embed.add');
        if ($this->val->check()) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status' => 'error',
                    'message' => implode(', ', $this->val->errors),
                    'errors' => $this->val->errors
                ]);
                exit;
            }
            foreach ($_POST as $key => $val) {
                $this->theme->set($key, Req::Post_Safe($key));
            }
            $this->theme->set('errors', $this->val->errors);
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
            $this->mod_video_embed_model->user_id = $this->session->get('id');

            // Use the uploaded image when provided.
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_video_embed';
                $image->name = 'image';
                $option = $this->_videoImageOptions();
                if ($image->_resize($option)) {
                    $this->mod_video_embed_model->image = $image->url;
                } else {
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video_embed']['errors']['image_upload_failed']]);
                    exit;
                }
                $this->_error_message($this->lang['mod_video_embed']['errors']['image_upload_failed']);
                return FALSE;
                }
            } elseif ($autofillImage !== '') {
                $this->mod_video_embed_model->image = $autofillImage;
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
                        'message' => $this->lang['mod_video_embed']['message']['success']['add'],
                        'redirect' => $this->site['saddress'] . 'mod_video_embed/list'
                    ]);
                    exit;
                }
                $this->_success_message($this->lang['mod_video_embed']['message']['success']['add']);
            } else {
                // Roll back the uploaded image if persistence fails.
                if (isset($image->url)) {
                    $image->_rollback();
                }
                if ($autofillImage !== '') {
                    $this->_deleteVideoImageArtifacts($autofillImage);
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

    // Handle edit form submission.
    protected function _edit($id = NULL)
    {
        $this->val = Load::validator('mod_video_embed.edit');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                $this->theme->set($key, Req::Post_Safe($key));
            }
            $this->theme->set('errors', $this->val->errors);
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

            // Process the uploaded image when present.
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_video_embed';
                $image->name = 'image';
                $option = $this->_videoImageOptions();
                if ($image->_resize($option)) {
                    $new_image_url = $image->url;
                    // Delete the previous image before saving the new one.
                    $old_data = Load::model('mod_video_embed');
                    $old_data->where_id($id)->gRo()->sync();
                    if ($old_data->image != '') {
                        $image->mod = 'mod_video_embed';
                        $image->url = $old_data->image;
                        $image->_delete_files($option);
                    }
                    $this->mod_video_embed_model->image = $new_image_url;
                } else {
                    $this->_error_message($this->lang['mod_video_embed']['errors']['image_upload_failed']);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_video_embed_model->image)) {
                    unset($this->mod_video_embed_model->image);
                }
            }

            $this->mod_video_embed_model->id = $id;
            if ($this->mod_video_embed_model->update('id')) {
                $this->_success_message($this->lang['mod_video_embed']['message']['success']['edit']);
            } else {
                // Roll back the uploaded image if persistence fails.
                if (isset($image->url)) {
                    $image->_rollback();
                }
                $this->_error_message($this->lang['mod_video_embed']['message']['error']['edit']);
            }
        }
    }



    public function delete()
    {
        // Check delete permission.
        if ($this->user->hasPermission('mod_video_embed',  'delete', $this->lang['mod_video_embed']['role_errors'][ 'delete']) === False) {
            return False;
        }
        if (Req::is_Post('delete')) {
            if ($this->_delete(Req::Post('delete'))) {
                echo 'Success';
            } else {
                echo 'HATA';
            }
        } else {
            exit($this->lang['mod_video_embed']['errors']['record']);
        }
    }

    protected function _delete($delete)
    {
        $this->mod_video_embed_model = Load::model('mod_video_embed');
        foreach ($delete as $s) {
            $this->mod_video_embed_model->flush();
            $this->mod_video_embed_model->where_id($s)->gRo();
            if ($this->mod_video_embed_model->getSize() === 0) {
                exit($this->lang['mod_video_embed']['errors']['record']);
            }
            $this->mod_video_embed_model->sync();
            // Check moderate permission if content belongs to another user.
            if ($this->user->checkPermission('mod_video_embed', 'moderate_delete', $this->mod_video_embed_model->user_id, $this->lang['mod_video_embed']['role_errors']['moderate_delete']) === False) {
                return False;
            }

            // Remove related galleries when the relation module is enabled.
            $this->mod = Load::model('mod_gallery_mod');
            if ($this->mod->exists_mod_name("mod_video_embed") === TRUE) {
                // Remove gallery data attached to this record.
                Run::import('mod_gallery', '_remove', ['mod_video_embed', $this->mod_video_embed_model->id]);
            }
            // Remove related files when the relation module is enabled.
            $this->mod = Load::model('mod_attachment_mod');
            if ($this->mod->exists_mod_name("mod_video_embed") === TRUE) {
                // Remove file data attached to this record.
                Run::import('mod_attachment', '_remove', ['mod_video_embed', $this->mod_video_embed_model->id]);
            }

            if ($this->mod_video_embed_model->image != '') {
                $image = Load::mod('image');
                $image->mod = 'mod_video_embed';
                $image->name = 'image';
                $image->url = $this->mod_video_embed_model->image;
                $option = $this->_videoImageOptions();
                if ($image->_delete_files($option) !== TRUE) {
                    echo $this->lang['mod_video_embed']['errors']['image_delete_failed'];
                }
                $this->_deleteVideoImageArtifacts($this->mod_video_embed_model->image);
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
        $colomns[] = 'slider';
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

        if (Req::is_Post('search') && count(Req::Post_Safe('search')) > 0) {
            foreach (Req::Post_Safe('search') as $k => $v) {
                if (in_array($k, $colomns) && $v != '') {
                    $wheres[] = $k . " LIKE '%" . Req::Clear($v) . "%'";
                }
            }
        }

        if (Req::is_Post('search_radio') && count(Req::Post_Safe('search_radio')) > 0) {
            foreach (Req::Post_Safe('search_radio') as $k => $v) {
                if (in_array($k, $colomns) && $v != '') {
                    $wheres[] = $k . "='" . Req::Clear($v) . "'";
                }
            }
        }
        if (Req::is_Post('search_created_at') && count(Req::Post_Safe('search_created_at')) > 0) {
            foreach (Req::Post_Safe('search_created_at') as $k => $v) {
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
            (Role::hasPermission('mod_video_embed', 'moderate_edit') !== TRUE)
            and
            (Role::hasPermission('mod_video_embed', 'moderate_delete') !== TRUE)
        ) {
            $wheres[] = "user_id='" . $this->session->get('id') . "'";
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
                if (Role::hasPermission('mod_video_embed', 'edit')) {
                    $last .= '<button onclick="window.location.href=\''. $this->site['saddress'] .'mod_video_embed/edit/' . $content['id'] . '\';return false;" class="btn default btn-xs purple">
    <i class="fa fa-edit"></i> ' . $this->lang['mod_video_embed']['button']['edit'] . ' </button>';
                }
                if (Role::hasPermission('mod_video_embed',  'delete')) {
                    $last .= '<button onclick="mod_video_embed_table_ajax.akm.processDelete(\''. $content['id'] .'\'); return false;" class="btn default btn-xs red">
    <i class="fa fa-trash-o"></i> ' . $this->lang['mod_video_embed']['button'][ 'delete'] . ' </button>';
                }

                if (Role::hasPermission('mod_video_embed', 'gallery')) {
                    $last .= '<button onclick="mod_video_embed_table_ajax.akm.processGallery(\''. $content['id'] .'\'); return false;" class="btn default btn-xs blue">
    <i class="fa fa-file-image-o"></i> ' . $this->lang['mod_video_embed']['button']['gallery'] . ' </button>';
                }

                if (Role::hasPermission('mod_video_embed', 'file')) {
                    $last .= '<button onclick="mod_video_embed_table_ajax.akm.processDosya(\''. $content['id'] .'\'); return false;" class="btn default btn-xs green">
    <i class="fa fa-file-zip-o"></i> ' . $this->lang['mod_video_embed']['button']['file'] . ' </button>';
                }
                $data[] = [
                    '<input type="checkbox" name="id[]" value="'. $content['id'] .'">',
                    $content['id'],
                    $content['name'],
                    '<img src="' . $this->site['iaddress'] . 'mod_video_embed/thumb/' . $content['image'] . '?v=' . time() . '">',
                    $content['duration'],
                    $content['lang'],
                    $content['created_at'],
                    '<span class="label label-' . $status_list[$content['status']]['0'] . '">' . $status_list[$content['status']]['1'] . '</span>',
                    $last,
                    $content['slider']
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




    // Publish the selected records.
    public function publish()
    {
        // Check edit permission.
        if ($this->user->hasPermission('mod_video_embed', 'edit', $this->lang['mod_video_embed']['role_errors']['edit']) === False) {
            return False;
        }
        $query = Req::Post_Safe('query');
        if ($this->_publish($query) === TRUE) {
            echo 'Success';
        } else {
            echo 'ERROR';
        }
    }

    private function _publish($query = NULL)
    {
        // Load the video embed model.
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

    public function toggle_hero_slider()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($this->user->hasPermission('mod_video_embed', 'edit', $this->lang['mod_video_embed']['role_errors']['edit']) === false) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video_embed']['role_errors']['edit']]);
            return;
        }

        $id = intval(Req::Post_Safe('id'));
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video_embed']['errors']['record']]);
            return;
        }

        $this->mod_video_embed_model = Load::model('mod_video_embed');
        $this->mod_video_embed_model->where_id($id)->gRo();
        if ($this->mod_video_embed_model->getSize() === 0) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video_embed']['errors']['record']]);
            return;
        }
        $this->mod_video_embed_model->sync();

        $new_slider = ($this->mod_video_embed_model->slider === 'y') ? 'n' : 'y';
        
        $update_model = Load::model('mod_video_embed');
        $update_model->id = $id;
        $update_model->slider = $new_slider;

        if ($update_model->update('id')) {
            echo json_encode([
                'status' => 'success',
                'in_hero_slider' => $new_slider === 'y',
                'slider' => $new_slider,
                'message' => $new_slider === 'y'
                    ? $this->lang['mod_video_embed']['hero_slider_added']
                    : $this->lang['mod_video_embed']['hero_slider_removed']
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video_embed']['hero_slider_error']]);
        }
    }

    // Unpublish the selected records.
    public function unpublish()
    {
        // Check edit permission.
        if ($this->user->hasPermission('mod_video_embed', 'edit', $this->lang['mod_video_embed']['role_errors']['edit']) === False) {
            return False;
        }
        $query = Req::Post_Safe('query');
        if ($this->_unpublish($query) === TRUE) {
            echo 'Success';
        } else {
            echo 'ERROR';
        }
    }

    private function _unpublish($query = NULL)
    {
        // Load the video embed model.
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

    // Store a success message for the template.
    protected function _success_message($message)
    {
        $this->theme->set('Success', True);
        $this->theme->set('message', $message);
    }

    // Store a validation error message for the template.
    protected function _error_message($message)
    {
        $this->theme->set('error', True);
        $this->theme->set('errors', [$message]);
    }

    // Store a fatal error message for the template.
    protected function _error($message)
    {
        $this->theme->set('error', TRUE);
        $this->theme->set('message', $message);
    }

    // Check whether the request should be treated as AJAX.
    private function _isAjaxRequest()
    {
        // Fast path: explicit POST flag.
        if (Req::is_Post('ajax_request') && Req::Post_Safe('ajax_request') === '1') {
            return true;
        }

        // Fallback: standard AJAX header.
        return (
            !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        );
    }

    private function _jsonResponse(array $payload)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
    }

    private function _autofillMessage($key)
    {
        $messages = $this->lang['mod_video_embed']['autofill'];
        return $messages[$key] ?? $messages['generic_error'];
    }

    private function _autofillThrowableMessage(Throwable $e)
    {
        $message = $e->getMessage();
        $messages = $this->lang['mod_video_embed']['autofill'];
        if ($message !== '' && in_array($message, $messages, true)) {
            return $message;
        }

        return $messages['generic_error'];
    }

    private function _extractYouTubeVideoId($input)
    {
        $input = trim((string) $input);
        if ($input === '') {
            return null;
        }

        if (preg_match('/youtube\.com\/embed\/([A-Za-z0-9_-]{11})/i', $input, $matches)) {
            return $matches[1];
        }

        if (preg_match('/youtube\.com\/shorts\/([A-Za-z0-9_-]{11})/i', $input, $matches)) {
            return $matches[1];
        }

        if (preg_match('/youtu\.be\/([A-Za-z0-9_-]{11})/i', $input, $matches)) {
            return $matches[1];
        }

        if (preg_match('/[?&]v=([A-Za-z0-9_-]{11})/i', $input, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $input)) {
            return $input;
        }

        return null;
    }

    private function _buildEmbedCode($videoId)
    {
        $videoId = trim((string) $videoId);
        return '<iframe width="560" height="315" src="https://www.youtube.com/embed/' . $videoId . '" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>';
    }

    private function _fetchYouTubeMetadata($videoId)
    {
        $metadata = [
            'title' => '',
            'description' => '',
            'duration' => '',
            'thumbnail_url' => '',
            'source' => 'youtube_oembed',
        ];

        $apiKey = $this->_getYouTubeApiKey();
        if ($apiKey !== '') {
            $apiUrl = 'https://www.googleapis.com/youtube/v3/videos?id=' . rawurlencode($videoId) . '&part=snippet,contentDetails&key=' . rawurlencode($apiKey);
            $apiResponse = $this->_httpGetJson($apiUrl);

            if (isset($apiResponse['items'][0]['snippet']) && is_array($apiResponse['items'][0]['snippet'])) {
                $snippet = $apiResponse['items'][0]['snippet'];
                $metadata['title'] = trim((string) ($snippet['title'] ?? ''));
                $metadata['description'] = trim((string) ($snippet['description'] ?? ''));
                $metadata['thumbnail_url'] = $this->_pickYouTubeThumbnailUrl($snippet['thumbnails'] ?? []);
                $metadata['source'] = 'youtube_data_api';

                if (!empty($apiResponse['items'][0]['contentDetails']['duration'])) {
                    $metadata['duration'] = $this->_formatIso8601Duration($apiResponse['items'][0]['contentDetails']['duration']);
                }
            }
        }

        if ($metadata['title'] === '') {
            $oembedUrl = 'https://www.youtube.com/oembed?url=' . rawurlencode('https://www.youtube.com/watch?v=' . $videoId) . '&format=json';
            $oembed = $this->_httpGetJson($oembedUrl);

            if (is_array($oembed)) {
                $metadata['title'] = trim((string) ($oembed['title'] ?? ''));
                $metadata['thumbnail_url'] = trim((string) ($oembed['thumbnail_url'] ?? ''));
                $metadata['source'] = 'youtube_oembed';
            }
        }

        // Scrape watch page if duration is not resolved yet
        if ($metadata['duration'] === '') {
            $watchUrl = 'https://www.youtube.com/watch?v=' . $videoId;
            $watchResponse = $this->_httpRequest($watchUrl);
            if (!empty($watchResponse['body'])) {
                $html = $watchResponse['body'];

                // 1. Try itemprop="duration" content="..." (ISO 8601 format like PT3M34S)
                if (preg_match('/itemprop="duration"\s+content="([^"]+)"/i', $html, $matches)) {
                    $metadata['duration'] = $this->_formatIso8601Duration($matches[1]);
                }

                // 2. Try "lengthSeconds":"..." as fallback
                if ($metadata['duration'] === '' && preg_match('/"lengthSeconds"\s*:\s*"(\d+)"/i', $html, $matches)) {
                    $seconds = (int) $matches[1];
                    $h = floor($seconds / 3600);
                    $m = floor(($seconds % 3600) / 60);
                    $s = $seconds % 60;
                    $metadata['duration'] = $h > 0 ? sprintf('%02d:%02d:%02d', $h, $m, $s) : sprintf('%02d:%02d', $m, $s);
                }

                // 3. Fallback for title/description if still empty
                if ($metadata['title'] === '' && preg_match('/<meta\s+name="title"\s+content="([^"]+)"/i', $html, $matches)) {
                    $metadata['title'] = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
                }
                if ($metadata['description'] === '' && preg_match('/<meta\s+name="description"\s+content="([^"]+)"/i', $html, $matches)) {
                    $metadata['description'] = html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
                }
            }
        }

        if ($metadata['thumbnail_url'] === '') {
            foreach ($this->_youtubeThumbnailCandidates($videoId) as $candidateUrl) {
                if ($this->_remoteImageExists($candidateUrl)) {
                    $metadata['thumbnail_url'] = $candidateUrl;
                    break;
                }
            }
        }

        if ($metadata['title'] === '') {
            throw new Exception($this->_autofillMessage('fetch_failed'));
        }

        return $metadata;
    }

    private function _getYouTubeApiKey()
    {
        $candidates = [
            getenv('YOUTUBE_DATA_API_KEY'),
            getenv('YOUTUBE_API_KEY'),
            $_ENV['YOUTUBE_DATA_API_KEY'] ?? '',
            $_ENV['YOUTUBE_API_KEY'] ?? '',
            $_SERVER['YOUTUBE_DATA_API_KEY'] ?? '',
            $_SERVER['YOUTUBE_API_KEY'] ?? '',
            $this->site['youtube_data_api_key'] ?? '',
            $this->site['youtube_api_key'] ?? '',
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    private function _httpGetJson($url)
    {
        $response = $this->_httpRequest($url);
        if ((int) ($response['status'] ?? 0) < 200 || (int) ($response['status'] ?? 0) >= 300) {
            return null;
        }

        $decoded = json_decode((string) ($response['body'] ?? ''), true);
        return is_array($decoded) ? $decoded : null;
    }

    private function _httpRequest($url)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_USERAGENT => 'RojnameVideoEmbed/1.0',
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HEADER => true,
            ]);

            $result = curl_exec($ch);
            if ($result === false) {
                curl_close($ch);
                return ['status' => 0, 'headers' => [], 'body' => ''];
            }

            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $body = substr($result, $headerSize);
            $headerText = substr($result, 0, $headerSize);
            curl_close($ch);

            return [
                'status' => $status,
                'headers' => $this->_parseResponseHeaders($headerText),
                'body' => $body,
            ];
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 20,
                'ignore_errors' => true,
                'header' => "User-Agent: RojnameVideoEmbed/1.0\r\n",
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        $headers = http_get_last_response_headers() ?? [];
        $status = 0;
        if (!empty($headers[0]) && preg_match('/\s(\d{3})\s/', $headers[0], $matches)) {
            $status = (int) $matches[1];
        }

        return [
            'status' => $status,
            'headers' => $this->_parseResponseHeaders(is_array($headers) ? implode("\n", $headers) : ''),
            'body' => $body !== false ? $body : '',
        ];
    }

    private function _parseResponseHeaders($headerText)
    {
        $headers = [];
        foreach (preg_split("/\r\n|\n|\r/", (string) $headerText) as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $headers[strtolower(trim($name))] = trim($value);
        }

        return $headers;
    }

    private function _pickYouTubeThumbnailUrl($thumbnails)
    {
        $priority = ['maxres', 'standard', 'high', 'medium', 'default'];
        foreach ($priority as $key) {
            if (!empty($thumbnails[$key]['url'])) {
                return (string) $thumbnails[$key]['url'];
            }
        }

        return '';
    }

    private function _youtubeThumbnailCandidates($videoId)
    {
        return [
            'https://i.ytimg.com/vi/' . $videoId . '/maxresdefault.jpg',
            'https://i.ytimg.com/vi/' . $videoId . '/sddefault.jpg',
            'https://i.ytimg.com/vi/' . $videoId . '/hqdefault.jpg',
            'https://i.ytimg.com/vi/' . $videoId . '/mqdefault.jpg',
            'https://i.ytimg.com/vi/' . $videoId . '/default.jpg',
        ];
    }

    private function _remoteImageExists($url)
    {
        $response = $this->_httpRequest($url);
        $contentType = strtolower((string) ($response['headers']['content-type'] ?? ''));

        return (int) ($response['status'] ?? 0) >= 200
            && (int) ($response['status'] ?? 0) < 300
            && strpos($contentType, 'image/') === 0;
    }

    private function _importRemoteThumbnail($url)
    {
        $response = $this->_httpRequest($url);
        $status = (int) ($response['status'] ?? 0);
        $contentType = strtolower((string) ($response['headers']['content-type'] ?? ''));
        $body = (string) ($response['body'] ?? '');

        if ($status < 200 || $status >= 300 || strpos($contentType, 'image/') !== 0 || $body === '') {
            throw new Exception($this->_autofillMessage('download_failed'));
        }

        $extension = $this->_extensionFromContentType($contentType);
        $tempPath = tempnam(sys_get_temp_dir(), 'ytthumb_');
        if ($tempPath === false) {
            throw new Exception($this->_autofillMessage('download_failed'));
        }

        $tempFile = $tempPath . '.' . $extension;
        @rename($tempPath, $tempFile);
        file_put_contents($tempFile, $body);

        $originalFiles = $_FILES;
        $_FILES['image'] = [
            'name' => 'youtube-thumbnail.' . $extension,
            'type' => $contentType,
            'tmp_name' => $tempFile,
            'error' => 0,
            'size' => filesize($tempFile),
        ];

        try {
            $image = Load::mod('image');
            $image->mod = 'mod_video_embed';
            $image->name = 'image';
            $option = $this->_videoImageOptions();

            if (!$image->_resize($option)) {
                throw new Exception($this->_autofillMessage('download_failed'));
            }

            return (string) $image->url;
        } finally {
            $_FILES = $originalFiles;
            @unlink($tempFile);
        }
    }

    private function _extensionFromContentType($contentType)
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        return $map[$contentType] ?? 'jpg';
    }

    private function _deleteVideoImageArtifacts($filename)
    {
        $filename = basename((string) $filename);
        if ($filename === '') {
            return;
        }

        $paths = [
            root_dir . 'static' . DS . 'mod_video_embed' . DS . $filename,
            root_dir . 'static' . DS . 'mod_video_embed' . DS . 'thumb' . DS . $filename,
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function _videoImageOptions()
    {
        return [
            '/' => [
                'width' => self::VIDEO_IMAGE_WIDTH,
                'height' => NULL,
                'option' => 'width',
            ],
            '/thumb' => [
                'width' => self::VIDEO_THUMB_WIDTH,
                'height' => NULL,
                'option' => 'width',
            ],
        ];
    }

    private function _formatIso8601Duration($duration)
    {
        try {
            $interval = new DateInterval($duration);
        } catch (Throwable $e) {
            return '';
        }

        $hours = ($interval->d * 24) + $interval->h;
        $minutes = $interval->i;
        $seconds = $interval->s;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    private function _makeKeywordString($title)
    {
        $title = trim((string) $title);
        if ($title === '') {
            return '';
        }

        $parts = preg_split('/\s+/', strtolower($title));
        $parts = array_filter(array_unique(array_map(static function ($part) {
            return trim($part, " \t\n\r\0\x0B.,!?;:\"'()[]{}");
        }, $parts)));

        return implode(',', $parts);
    }
}
