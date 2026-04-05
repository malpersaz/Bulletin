<?php
/* AKM Software.
 * @2015
 * mod_video_embed Module Page
 * mod/mod_video_embed/index.php
 */
class mod_video_embed_mod extends indexMain
{
    public $mod_video_embed_model;
    private const VIDEO_IMAGE_WIDTH = 1280;
    private const VIDEO_THUMB_WIDTH = 640;
    function __construct()
    {
        parent::__construct();
        // Load the template library.
        $this->tema = Load::lib('AdminTemplate');
        // Load the database library.
        $this->db = Load::lib('Database');
        // Site settings remain available through shared state when needed.
        //$this->site = Kayit::getir('site_config');
        // Load the module language file.
        $this->lang = Load::lang('mod_video_embed');
        // Pass language data to the template layer.
        $this->tema->lang($this->lang);
        // Load the session library.
        $this->session = Load::lib('Session');
        // Load the user model.
        $this->user = Load::model('mod_user.user');
    }
    // Render the add form.
    public function add()
    {
        // Check add permission.
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
            $this->tema->tpl('mod_video_embed/edit');
            return;
        }

        $d = $this->mod_video_embed_model->getResultOne()->toArray();

        // Check moderate permission if content belongs to another user.
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

    // Render the list page.
    public function list()
    {
        // Check list permission.
        if ($this->user->hasPermission('mod_video_embed', 'list', $this->lang['mod_video_embed']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_video_embed']['list'];
        $this->tema->tpl('mod_video_embed/list');
        $this->tema->veri('site', $this->site);
    }

    public function autofill_metadata()
    {
        if ($this->user->hasPermission('mod_video_embed', 'add', $this->lang['mod_video_embed']['role_errors']['add'], 1) === False) {
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
                    $imageUrl = $this->site['radres'] . 'mod_video_embed/thumb/' . $imageFile . '?v=' . time();
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
            $this->_jsonResponse([
                'status' => 'error',
                'message' => $e->getMessage() ?: $this->_autofillMessage('generic_error'),
            ]);
        }
    }

    // AJAX image upload from the list screen.
    public function ajax_image_upload()
    {
        // Check edit permission.
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

        // Check moderate permission if content belongs to another user.
        if ($this->user->checkPermission('mod_video_embed', 'moderate_edit', $this->mod_video_embed_model->user_id, $this->lang['mod_video_embed']['role_errors']['moderate_edit']) === False) {
            echo json_encode(['status' => 'error', 'message' => 'Bu içeriği düzenleme permissionniz yok']);
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
                echo 'Oke';
            } else {
                echo $image->error;
                return FALSE;
            }
        } else {
            echo 'Oke';
        }
    }

    // Handle add form submission.
    private function _add()
    {
        $isAjax = $this->_isAjaxRequest();
        $autofillImage = Req::Post_Safe('autofill_image');
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
                    echo json_encode(['status' => 'error', 'message' => $image->error]);
                    exit;
                }
                $this->_error_message($image->error);
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
                        'message' => $this->lang['mod_video_embed']['message']['oke']['add'],
                        'redirect' => $this->site['sadres'] . 'mod_video_embed/list'
                    ]);
                    exit;
                }
                $this->_oke_message($this->lang['mod_video_embed']['message']['oke']['add']);
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
                    echo $image->error;
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




    // Publish the selected records.
    public function publish()
    {
        // Check edit permission.
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
    // Unpublish the selected records.
    public function unpublish()
    {
        // Check edit permission.
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
    private function _oke_message($mesaj)
    {
        $this->tema->veri('oke', True);
        $this->tema->veri('mesaj', $mesaj);
    }

    // Store a validation error message for the template.
    private function _error_message($mesaj)
    {
        $this->tema->veri('hata', True);
        $this->tema->veri('hatalar', [$mesaj]);
    }

    // Store a fatal error message for the template.
    private function _error($mesaj)
    {
        $this->tema->veri('error', TRUE);
        $this->tema->veri('mesaj', $mesaj);
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
        $messages = [
            'permission_denied' => 'You do not have permission to fetch video details.',
            'missing_source' => 'Paste a YouTube URL or embed code first.',
            'invalid_source' => 'A valid YouTube video could not be detected from the provided input.',
            'success' => 'Video details were imported from YouTube.',
            'generic_error' => 'Video details could not be imported.',
            'fetch_failed' => 'YouTube metadata could not be fetched.',
            'download_failed' => 'Thumbnail image could not be downloaded.',
        ];

        return $messages[$key] ?? $messages['generic_error'];
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
                throw new Exception($image->error ?: $this->_autofillMessage('download_failed'));
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
