<?php
/* 
 * AKM Software.
 * @2010
 * Video Module Page
 * mod/mod_video/index.php
 */



class mod_video_mod extends indexMain
{
    public $grid;
    public $mod_video_model;

    function __construct()
    {

        parent::__construct();
        // Load Template Library
        $this->tema = Load::lib('AdminTemplate');
        // Load Database Library
        $this->db = Load::lib('Database');
        // Get site settings
        //$this->site = Kayit::getir('site_config');
        // Load Language File
        $this->lang = Load::lang('mod_video');
        // Send Language File to Template
        $this->tema->lang($this->lang);
        if (Req::is_Post('sid')) {
            Kayit::ekle('session_sid', Req::Post_Safe('sid'));
        }
        // Session Library
        $this->session = Load::lib('Session');
        // User Model
        $this->user = Load::model('mod_user.user');
    }

    public function _emesaj($komut, $mesaj)
    {
        echo json_encode(['hata' => true, 'komut' => $komut, 'mesaj' => sprintf($this->lang['mod_video']['19'], $mesaj)]);
    }

    public function cevir()
    {
        if ($this->user->hasPermission('mod_video', 'add', $this->lang['mod_video']['role_errors']['add'], 3) === False) {
            return False;
        }
        // require root_dir . 'vendor/autoload.php';

        // Posted Video File
        $videop = Req::Post_Safe('video');

        // Video Conversion TEMP directory
        $tmp = root_dir . 'cache' . DS . 'video_tmp' . DS;
        $log = root_dir . 'cache' . DS . 'video_log' . DS;

        $video_file = $tmp . $videop;
        // Output directory for converted video
        $video_out_dir = root_dir . 'cache' . DS . 'video_tmp' . DS;
        // Output directory for extracted frame (image)
        $image_out_dir = root_dir . 'cache' . DS . 'video_tmp' . DS;

        // Converted video output filename
        $video_out = Helper::randomkey('14') . ".mp4";
        $video_out_file = $video_out_dir . $video_out;
        // Extracted frame output filename
        $image_out = Helper::randomkey('14') . ".jpg";
        $image_out_file = $image_out_dir . $image_out;


        $config = new \PHPVideoToolkit\Config([
            'temp_directory' => './tmp',
            'ffmpeg' => '/usr/bin/ffmpeg',
            'ffprobe' => '/usr/bin/ffprobe',
            //'yamdi' => '/opt/local/bin/yamdi',
            //'qtfaststart' => '/opt/local/bin/qt-faststart',
            'temp_directory' => $tmp,
        ], true);

        $ffmpeg = new \PHPVideoToolkit\FfmpegParser();
        $is_available = $ffmpeg->isAvailable(); // returns boolean
        $ffmpeg_version = $ffmpeg->getVersion(); // outputs something like - array('version'=>1.0, 'build'=>null)

        if (!$ffmpeg->isAvailable()) {
            $this->_mesaj("hata", print_r($config, true), "FFMPEG BULUNAMADI");
            exit;
        }


        // Video Information
        try {

            $parser = new \PHPVideoToolkit\MediaParser();
            $data = $parser->getFileInformation($video_file);
            //echo '<pre>'.print_r($data['duration']->getTimecode("%hh:%ss:%mm"), true).'</pre>';

        } catch (\PHPVideoToolkit\FfmpegProcessOutputException $e) {
            $process = $parser->getProcess();
            $this->_emesaj($process->getExecutedCommand(), "Bilgi : " . $e->getMessage());
            exit;
        } catch (\PHPVideoToolkit\Exception $e) {
            $process = $parser->getProcess();
            $this->_emesaj($process->getExecutedCommand(), "Bilgi : " . $e->getMessage());
            exit;
        }


        // Convert Video
        try {
            $video = new \PHPVideoToolkit\Video($video_file);
            $output_format = new \PHPVideoToolkit\VideoFormat();
            $output_format->setVideoDimensions(320, 240);
            $process = $video->save($video_out_file, $output_format, \PHPVideoToolkit\Video::OVERWRITE_EXISTING);
        } catch (\PHPVideoToolkit\FfmpegProcessOutputException $e) {
            $process = $video->getProcess();
            $this->_emesaj($process->getExecutedCommand(), "Video : " . $e->getMessage());
            exit;
        } catch (\PHPVideoToolkit\Exception $e) {
            $process = $video->getProcess();
            $this->_emesaj($process->getExecutedCommand(), "Video : " . $e->getMessage());
            exit;
        }


        try {
            $image = new \PHPVideoToolkit\Video($video_file);
            $image->extractFrame(new \PHPVideoToolkit\Timecode(5));
            $output_format = new \PHPVideoToolkit\ImageFormat_Jpeg();
            $output_format->setVideoDimensions(320, 240);
            $iprocess = $image->save($image_out_file, $output_format);
        } catch (\PHPVideoToolkit\FfmpegProcessOutputException $e) {
            $iprocess = $image->getProcess();
            $this->_emesaj($iprocess->getExecutedCommand(), "Resim : " . $e->getMessage());
            exit;
        } catch (\PHPVideoToolkit\Exception $e) {
            $iprocess = $image->getProcess();
            $this->_emesaj($iprocess->getExecutedCommand(), "Resim : " . $e->getMessage());
            exit;
        }

        $a = [];
        $a['video_islem'] = $process->getRunTime();
        $a['video_sure'] = $data['duration']->getTimecode('%hh:%mm:%ss', true);
        $a['video_dosya'] = $video_out;
        $a['video_komut'] = $process->getExecutedCommand();
        $a['video_original'] = $video_file;
        $a['resim_dosya'] = $image_out;
        $a['resim_komut'] = $iprocess->getExecutedCommand();
        $a['oke'] = true;
        $a['komut'] = $a['video_komut'];
        $a['mesaj'] = sprintf($this->lang['mod_video']['20'], $a['video_islem']);
        echo json_encode($a);
    }

    public function up()
    {
        // Check Add Permission
        if (Roll::hasPermission('mod_video', 'add') === False) {
            $ar = ['hata' => true, 'mesaj' => $this->lang['mod_video']['role_errors']['add']];
            echo json_encode($ar);
            exit();
        }

        $uzanti = Helper::extension($_FILES['Filedata']['name']);
        $name = Helper::randomkey('10') . '.' . $uzanti;
        $valid_array = ['mov', 'mpeg', 'mpg', 'mp4', 'flv', 'wmv', 'avi'];

        if (!in_array($uzanti, $valid_array)) {
            $ar = ['hata' => true, 'mesaj' => sprintf($this->lang['mod_video']['errors']['in_valid_ext'], implode(',', $valid_array))];
            echo json_encode($ar);
            exit();
        }

        $hedef = root_dir . 'cache/video_tmp/' . $name;
        $tmp = $_FILES['Filedata']['tmp_name'];

        if (
            move_uploaded_file($tmp, $hedef)
            &&
            $_FILES['Filedata']['error'] === UPLOAD_ERR_OK
        ) {
            $ar = ['oke' => true, 'mesaj' => $this->lang['mod_video']['converted'], 'file' => $name];
            echo json_encode($ar);
            exit();
        }
        $error_message = $this->_file_upload_error_message($_FILES['Filedata']['error']);
        $ar = ['hata' => true, 'mesaj' => "An error occurred:" . $error_message];
        echo json_encode($ar);
        exit();
    }

    private function _file_upload_error_message($error_code)
    {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }


    /**
     * Add Page
     */
    public function add()
    {
        // Check Add Permission
        if ($this->user->hasPermission('mod_video', 'add', $this->lang['mod_video']['role_errors']['add'], 1) === False) {
            return False;
        }
        if (Req::is_Post('add')) {
            $this->_add();
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_video']['add'];
        $this->tema->tpl('mod_video/ekle');
        $this->tema->veri('site', $this->site);
        $this->db->fetch_array("SELECT name,seo FROM `lang` ORDER BY seo DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $this->db->fetch_array("SELECT name,id FROM `mod_acl` ORDER BY id DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());
    }

    /**
     * Edit Page
     */
    public function edit($id = NULL)
    {
        // Check Edit Permission
        if ($this->user->hasPermission('mod_video', 'edit', $this->lang['mod_video']['role_errors']['edit'], 1) === False) {
            return False;
        }
        $this->mod_video_model = Load::model('mod_video');
        $id = Req::Url_Clear($id);
        if ($this->mod_video_model->exists_id($id) === False) {
            $this->_error($this->lang['mod_video']['errors']['record']);
            return;
        }

        $d = $this->mod_video_model->getResultOne()->toArray();

        // Check Moderate Permission if content belongs to another user
        if ($this->user->checkPermission('mod_video', 'moderate_edit', $d['user_id'], $this->lang['mod_video']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id);
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_video']['edit'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        $this->tema->tpl('mod_video/edit');
        $this->db->fetch_array("SELECT name,seo FROM `lang` ORDER BY seo DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $this->db->fetch_array("SELECT name,id FROM `mod_acl` ORDER BY id DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());
    }

    /**
     * List Page
     */
    public function list()
    {
        // Check List Permission
        if ($this->user->hasPermission('mod_video', 'list', $this->lang['mod_video']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_video']['list'];
        $this->tema->tpl('mod_video/list');
        $this->tema->veri('site', $this->site);
    }

    /**
     * XML Page
     */
    public function xml()
    {
        // Check List Permission
        if ($this->user->hasPermission('mod_video', 'list', $this->lang['mod_video']['role_errors']['list'], 1) === False) {
            return False;
        }
        $this->grid = Load::model('mod_video.grid');
        return $this->grid->xml();
    }

    public function image_remove_image()
    {
        // Check Edit Permission
        if ($this->user->hasPermission('mod_video', 'edit', $this->lang['mod_video']['role_errors']['edit'], 1) === False) {
            return FALSE;
        }

        $id = intval(Req::Post_Safe('id'));

        $this->mod_video_model = Load::model('mod_video');
        $this->mod_video_model->where_id($id)->gRo();
        if ($this->mod_video_model->getSize() === 0) {
            echo $this->lang['mod_video']['errors']['record'];
            return FALSE;
        }


        $this->mod_video_model->sync();
        // Check Moderate Permission if content belongs to another user
        if ($this->user->checkPermission('mod_video', 'moderate_edit', $this->mod_video_model->user_id, $this->lang['mod_video']['role_errors']['moderate_edit']) === False) {
            return False;
        }

        if ($this->mod_video_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_video';
            $image->url = $this->mod_video_model->image;
            $option = [
                '/image' =>
                [
                    'width' => 250,
                    'height' => NULL,
                    'option' => 'width',
                ]
            ];
            if ($image->_delete_files($option)) {
                $this->mod_video_model->image = '';
                $this->mod_video_model->update('id');
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
        $this->val = Load::validator('mod_video.ekle');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_video_model = Load::model('mod_video');

            $this->mod_video_model->lang = Req::Post_Safe('lang');
            $this->mod_video_model->name = Req::Post_Safe('name');
            $this->mod_video_model->sure = Req::Post_Safe('sure');
            $this->mod_video_model->created_at = date('Y-m-d H:i:s');

            $this->mod_video_model->status = Req::Post_Safe('status');

            $this->mod_video_model->description = Req::Post_Safe('description');

            $this->mod_video_model->acl_id = Req::Post_Safe('acl_id');
            $this->mod_video_model->user_id = $this->session->getir('id');

            $this->mod_video_model->flv = basename(Req::Post_Safe('flv'));
            $this->mod_video_model->image = basename(Req::Post_Safe('image'));

            $vid = basename(Req::Post_Safe('video_original'));
            $cache_flv = root_dir . 'cache' . DS . 'video_tmp' . DS . $this->mod_video_model->flv;
            $cache_resim = root_dir . 'cache' . DS . 'video_tmp' . DS . $this->mod_video_model->image;
            $cache_video = root_dir . 'cache' . DS . 'video_tmp' . DS . $vid;

            $real_flv = root_dir . 'static' . DS . 'mod_video' . DS . 'flv' . DS . $this->mod_video_model->flv;
            $real_resim = root_dir . 'static' . DS . 'mod_video' . DS . 'image' . DS . $this->mod_video_model->image;
            $real_video = root_dir . 'static' . DS . 'mod_video' . DS . 'original' . DS . $vid;


            $uzanti = Helper::extension($vid);
            $valid_array = ['mov', 'mpeg', 'mpg', 'mp4', 'flv', 'wmv', 'avi'];
            if (!in_array($uzanti, $valid_array)) {
                @unlink($cache_flv);
                @unlink($cache_resim);
                @unlink($cache_video);
                return $this->_error(print_r($_POST, TRUE) . sprintf($this->lang['mod_video']['errors']['in_valid_ext_original'], implode(',', $valid_array)));
            }


            // Move converted video to static directory
            if (copy($cache_flv, $real_flv) === FALSE) {
                @unlink($cache_flv);
                @unlink($cache_resim);
                @unlink($cache_video);
                return $this->_error($this->lang['mod_video']['errors']['1']);
            }
            @unlink($cache_flv);

            // Move image to static directory
            if (copy($cache_resim, $real_resim) === FALSE) {
                @unlink($cache_flv);
                @unlink($cache_resim);
                @unlink($cache_video);
                @unlink($real_flv);
                return $this->_error($this->lang['mod_video']['errors']['2']);
            }
            @unlink($cache_resim);


            // Save original video if requested
            if (Req::Post_Safe('unremove_original') === 'y') {
                if (copy($cache_video, $real_video) === FALSE) {
                    @unlink($cache_flv);
                    @unlink($cache_resim);
                    @unlink($cache_video);
                    @unlink($real_flv);
                    @unlink($real_resim);
                    return $this->_error($this->lang['mod_video']['errors']['3']);
                }
                $this->mod_video_model->original = $vid;
            } else {
                $this->mod_video_model->original = NULL;
            }
            // Remove Original Video
            @unlink($cache_video);


            if ($this->mod_video_model->save(TRUE)) {
                // Add to Remote Videos
                if (Req::Post_Safe('add_uzak') === 'y') {
                    $v = Load::model('mod_video_embed');

                    $cresim = root_dir . 'static' . DS . 'mod_video_embed' . DS . $this->mod_video_model->image;
                    $tresim = root_dir . 'static' . DS . 'mod_video_embed' . DS . 'thumb' . DS . $this->mod_video_model->image;

                    // Copy Image
                    copy($real_resim, $cresim);
                    copy($real_resim, $tresim);

                    $v->image = $this->mod_video_model->image;
                    $v->status = $this->mod_video_model->status;
                    $v->lang = $this->mod_video_model->lang;
                    $v->name = $this->mod_video_model->name;
                    $v->sure = $this->mod_video_model->sure;
                    $v->created_at = $this->mod_video_model->created_at;

                    $v->content = $this->mod_video_model->description;
                    $v->title = $this->mod_video_model->name;
                    $v->description = $this->mod_video_model->name;
                    $v->keywords = $this->mod_video_model->name;
                    $v->category_id = 4;
                    $v->acl_id = $this->mod_video_model->acl_id;
                    $v->user_id = $this->mod_video_model->user_id;
                    if ($this->mod_video_model->original !== NULL) {
                        $v->original = $this->mod_video_model->original;
                    }
                    $r = $this->site['radres'] . 'mod_video/image/' . $v->image;
                    $f = $this->site['radres'] . 'mod_video/flv/' . $this->mod_video_model->flv;
                    $l = $this->site['radres'] . 'static/mod_settings/logo.png';
                    $v->embed = '<video id="video" class="video-js vjs-default-skin vjs-big-play-centered" controls preload="auto" width="100%" height="240" poster="' . $r . '" data-setup=\'{"example_option":true}\'><source src="' . $f . '" type="video/mp4"/></video>';
                    //$v->embed = '<embed src="' . $this->site['radres'] . 'templates/base_static/swf/player.swf?file=' . $f . '&image=' . $r . '&logo=' . $l . '" quality="high" bgcolor="#000000" width="512" height="320" name="mymovie" align="middle" allowFullScreen="true" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.adobe.com/go/getflashplayer" />';
                    $v->save();
                }

                $this->_oke_message($this->lang['mod_video']['message']['oke']['add']);
            } else {
                @unlink($real_resim);
                @unlink($real_flv);
                if ($this->mod_video_model->original !== NULL) {
                    @unlink($real_video);
                }
                $this->_error_message($this->lang['mod_video']['message']['oke']['add']);
            }
        }
    }

    // Edit Function
    private function _edit($id = NULL)
    {
        $this->val = Load::validator('mod_video.edit');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_video_model = Load::model('mod_video');


            $this->mod_video_model->lang = Req::Post_Safe('lang');
            $this->mod_video_model->name = Req::Post_Safe('name');
            $this->mod_video_model->sure = Req::Post_Safe('sure');
            $this->mod_video_model->created_at = Req::Post_Safe('created_at');

            $this->mod_video_model->status = Req::Post_Safe('status');

            $this->mod_video_model->description = Req::Post_Safe('description');

            $this->mod_video_model->acl_id = Req::Post_Safe('acl_id');
            if (isset($this->mod_video_model->user_id)) {
                unset($this->mod_video_model->user_id);
            }

            // If Image exists
            if (isset($_FILES['image']['name']) && ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_video';
                $image->name = 'image';
                $option = [
                    '/image' =>
                    [
                        'width' => 250,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                ];
                if ($image->_resize($option)) {
                    $this->mod_video_model->image = $image->url;
                } else {
                    $this->_error_message($image->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_video_model->image)) {
                    unset($this->mod_video_model->image);
                }
            }

            $this->mod_video_model->id = $id;
            if ($this->mod_video_model->update('id')) {
                $this->_oke_message($this->lang['mod_video']['message']['oke']['edit']);
            } else {
                // Eğer Resim varsa
                if (isset($image->url)) {
                    $image->_rollback();
                }
                $this->_error_message($this->lang['mod_video']['message']['error']['edit']);
            }
        }
    }

    function json()
    {
        // Check List Permission
        if ($this->user->hasPermission('mod_video', 'list', $this->lang['mod_video']['role_errors']['list'], 1) === False) {
            return False;
        }
        $this->grid = Load::model('mod_video.flexigrid');
        return $this->grid->json();
    }


    function sil()
    {
        // Check Delete Permission
        if ($this->user->hasPermission('mod_video',  'delete', $this->lang['mod_video']['role_errors'][ 'delete']) === False) {
            return False;
        }
        if (Req::is_Post( 'delete')) {
            if ($this->_delete(Req::Post_Safe( 'delete'))) {
                echo 'Oke';
            } else {
                echo 'ERROR';
            }
        } else {
            exit($this->lang['mod_video']['errors']['record']);
        }
    }

    public function _delete($sil)
    {
        $this->mod_video_model = Load::model('mod_video');
        foreach ($sil as $s) {
            $this->mod_video_model->flush();
            $this->mod_video_model->getid($s)->gRo();
            if ($this->mod_video_model->getSize() === 0) {
                exit($this->lang['mod_video']['errors']['record']);
            }
            $this->mod_video_model->sync();
            // Check Moderate Permission if content belongs to another user
            if ($this->user->checkPermission('mod_video', 'moderate_delete', $this->mod_video_model->user_id, $this->lang['mod_video']['role_errors']['moderate_delete']) === False) {
                return False;
            }

            // Check if module exists and Gallery is being added
            $this->mod = Load::model('mod_gallery_mod');
            if ($this->mod->exists_mod_name("mod_video") === TRUE) {
                // Delete Gallery if exists for content
                Run::import('mod_gallery', '_remove', ['mod_video', $this->mod_video_model->id]);
            }


            // Delete original video
            if ($this->mod_video_model->original !== '') {
                @unlink(root_dir . 'static' . DS . 'mod_video' . DS . 'original' . DS . $this->mod_video_model->original);
            }
            // Delete converted video
            if ($this->mod_video_model->flv !== '') {
                @unlink(root_dir . 'static' . DS . 'mod_video' . DS . 'flv' . DS . $this->mod_video_model->flv);
            }
            // Delete image file
            if ($this->mod_video_model->image != '') {
                $image = Load::mod('image');
                $image->mod = 'mod_video';
                $image->name = 'image';
                $image->url = $this->mod_video_model->image;
                $option = [
                    '/image' =>
                    [
                        'width' => 250,
                        'height' => NULL,
                        'option' => 'width',
                    ]
                ];
                if ($image->_delete_files($option) !== TRUE) {
                    echo $image->error;
                }
            }


            $this->mod_video_model->delete();
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


        $i = Load::model('mod_video');


        $colomns = [];
        $colomns[] = 'id';
        $colomns[] = 'name';
        $colomns[] = 'image';
        $colomns[] = 'sure';
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
            if (isset($o[0]['column']) && ($o[0]['column'] > 0) && isset($colomns[$o[0]['column'] - 1])) {
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
                    } elseif (isset($v['from']) && $this->_valid_date($v['from'])) {
                        $wheres[] = $k . " > '" . $v['from'] . "'";
                    } elseif (isset($v['to']) && $this->_valid_date($v['to'])) {
                        $wheres[] = $k . " < '" . $v['to'] . "'";
                    }
                }
            }
        }
        if (
            (Roll::hasPermission('mod_video', 'moderate_edit') !== TRUE)
            and
            (Roll::hasPermission('mod_video', 'moderate_delete') !== TRUE)
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


            'y' => ['success', $this->lang['mod_video']['admin']['radio']['publish_yes']],


            'n' => ['danger', $this->lang['mod_video']['admin']['radio']['publish_no']],


        ];


        if ($getCount > 0) {
            foreach ($i->toArray() as $content) {

                $last = "";
                if (Roll::hasPermission('mod_video', 'edit')) {
                    $last .= '<button onclick="mod_video_table_ajax.akm.processEdit(' . $content['id'] . ');return false;" class="btn default btn-xs purple">
    <i class="fa fa-edit"></i> ' . $this->lang['mod_video']['button']['edit'] . ' </button>';
                }
                if (Roll::hasPermission('mod_video',  'delete')) {
                    $last .= '<button onclick="mod_video_table_ajax.akm.processSil(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs red">
    <i class="fa fa-trash-o"></i> ' . $this->lang['mod_video']['button'][ 'delete'] . ' </button>';
                }

                if (Roll::hasPermission('mod_video', 'gallery')) {
                    $last .= '<button onclick="mod_video_table_ajax.akm.processGallery(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs blue">
    <i class="fa fa-file-image-o"></i> ' . $this->lang['mod_video']['button']['gallery'] . ' </button>';
                }

                if (Roll::hasPermission('mod_video', 'file')) {
                    $last .= '<button onclick="mod_video_table_ajax.akm.processDosya(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs green">
    <i class="fa fa-file-zip-o"></i> ' . $this->lang['mod_video']['button']['file'] . ' </button>';
                }
                $data[] = [
                    '<input type="checkbox" name="id[]" value="' . $content['id'] . '">',
                    $content['id'],
                    $content['name'],
                    '<img src="' . $this->site['radres'] . 'mod_video/image/' . $content['image'] . '">',
                    $content['sure'],
                    $content['lang'],
                    $content['created_at'],
                    '<span class="label label-' . $status_list[$content['status']]['0'] . '">' . $status_list[$content['status']]['1'] . '</span>',
                    $last
                ];
            }
        }
        $ii = new mod_video_model();
        echo json_encode([
            'data' => $data,
            'recordsFiltered' => $getCount,
            'recordsTotal' => $ii->getCount(),
            // 'customActionStatus'=>'OK',
            ///'customActionMessage'=>'Oldumu Şimdi?'
        ]);
    }


    /**
     * Publish Page
     */
    public function publish()
    {
        // Check Edit Permission
        if ($this->user->hasPermission('mod_video', 'edit', $this->lang['mod_video']['role_errors']['edit']) === False) {
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
        // Load mod_video Model
        $this->mod_video_model = Load::model('mod_video');
        if (is_array($query)) {
            foreach ($query as $s) {
                $this->mod_video_model->id = $s;
                $this->mod_video_model->status = 'y';
                if ($this->mod_video_model->update('id')) {
                    continue;
                }
                return FALSE;
            }
            return TRUE;
        }
        return FALSE;
    }
    /**
     * Unpublish Page
     */
    public function unpublish()
    {
        // Check Edit Permission
        if ($this->user->hasPermission('mod_video', 'edit', $this->lang['mod_video']['role_errors']['edit']) === False) {
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
        // Load mod_video Model
        $this->mod_video_model = Load::model('mod_video');
        if (is_array($query)) {
            foreach ($query as $s) {
                $this->mod_video_model->id = $s;
                $this->mod_video_model->status = 'n';
                if ($this->mod_video_model->update('id')) {
                    continue;
                }
                return FALSE;
            }
            return TRUE;
        }
        return FALSE;
    }


    /**
     * Success Message
     */
    private function _oke_message($mesaj)
    {
        $this->tema->veri('oke', True);
        $this->tema->veri('mesaj', $mesaj);
    }

    /**
     * Error Message
     */
    private function _error_message($mesaj)
    {
        $this->tema->veri('hata', True);
        $this->tema->veri('hatalar', [$mesaj]);
    }

    /**
     * Error Message
     */
    private function _error($mesaj)
    {
        $this->tema->veri('error', TRUE);
        $this->tema->veri('mesaj', $mesaj);
    }
}
