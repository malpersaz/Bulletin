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

        if (!is_dir($tmp)) {
            @mkdir($tmp, 0777, true);
        }
        if (!is_dir($log)) {
            @mkdir($log, 0777, true);
        }

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


        $ffmpeg_path = '/usr/bin/ffmpeg';
        $ffprobe_path = '/usr/bin/ffprobe';
        
        $custom_ffmpeg = '/www/htdocs/w020f420/bin/ffmpeg';
        $custom_ffprobe = '/www/htdocs/w020f420/bin/ffprobe';

        // Detect dynamic ALL-INKL user directory if present in root_dir
        $dir_parts = explode(DIRECTORY_SEPARATOR, root_dir);
        $htdocs_idx = array_search('htdocs', $dir_parts);
        if ($htdocs_idx !== false && isset($dir_parts[$htdocs_idx + 1])) {
            $user_dir = $dir_parts[$htdocs_idx + 1];
            $custom_ffmpeg = DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'htdocs' . DIRECTORY_SEPARATOR . $user_dir . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'ffmpeg';
            $custom_ffprobe = DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'htdocs' . DIRECTORY_SEPARATOR . $user_dir . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'ffprobe';
        }

        $local_ffmpeg = root_dir . 'bin' . DS . 'ffmpeg';
        $local_ffprobe = root_dir . 'bin' . DS . 'ffprobe';

        if (file_exists($custom_ffmpeg) && is_executable($custom_ffmpeg)) {
            $ffmpeg_path = $custom_ffmpeg;
        } elseif (file_exists($local_ffmpeg) && is_executable($local_ffmpeg)) {
            $ffmpeg_path = $local_ffmpeg;
        }

        if (file_exists($custom_ffprobe) && is_executable($custom_ffprobe)) {
            $ffprobe_path = $custom_ffprobe;
        } elseif (file_exists($local_ffprobe) && is_executable($local_ffprobe)) {
            $ffprobe_path = $local_ffprobe;
        }

        // Verify native binary existence and executability before instantiating PHPVideoToolkit
        $has_ffmpeg = false;
        $has_ffprobe = false;

        if (file_exists($ffmpeg_path) && is_executable($ffmpeg_path)) {
            $has_ffmpeg = true;
        } else {
            $global_check = @shell_exec('which ffmpeg 2>&1');
            if (!empty($global_check) && strpos($global_check, 'not found') === false && strpos($global_check, 'no ffmpeg') === false) {
                $has_ffmpeg = true;
            }
        }

        if (file_exists($ffprobe_path) && is_executable($ffprobe_path)) {
            $has_ffprobe = true;
        } else {
            $global_check = @shell_exec('which ffprobe 2>&1');
            if (!empty($global_check) && strpos($global_check, 'not found') === false && strpos($global_check, 'no ffprobe') === false) {
                $has_ffprobe = true;
            }
        }

        $is_available = false;
        if ($has_ffmpeg && $has_ffprobe) {
            try {
                $config = new \PHPVideoToolkit\Config([
                    'temp_directory' => './tmp',
                    'ffmpeg' => $ffmpeg_path,
                    'ffprobe' => $ffprobe_path,
                    'temp_directory' => $tmp,
                ], true);

                $ffmpeg = new \PHPVideoToolkit\FfmpegParser();
                $is_available = $ffmpeg->isAvailable();
            } catch (\Throwable $e) {
                $is_available = false;
            }
        }

        if (!$is_available) {
            // Graceful Fallback if FFMPEG is not available on hosting
            $custom_duration = $this->get_mp4_duration($video_file);
            $a = [];
            $a['video_islem'] = 0;
            $a['video_sure'] = $custom_duration;
            $a['video_dosya'] = $videop; // Use original uploaded file
            $a['video_komut'] = 'N/A';
            $a['video_original'] = $videop;
            $a['resim_dosya'] = ''; // No thumbnail generated
            $a['resim_komut'] = 'N/A';
            $a['oke'] = true;
            $a['komut'] = 'N/A';
            $a['mesaj'] = "FFMPEG li ser hostingê nehat dîtin. Vîdyo bêyî veguherandin wekî orjînal hate qebûlkirin.";
            echo json_encode($a);
            exit;
        }

        // Try to parse and convert video using FFMPEG, with fallback on any failure
        try {
            // Video Information
            $total_seconds = 0.0;
            $parser = new \PHPVideoToolkit\MediaParser();
            $data = $parser->getFileInformation($video_file);
            if (isset($data['duration'])) {
                $total_seconds = (float) $data['duration']->total_seconds;
            }

            // Convert Video
            $video = new \PHPVideoToolkit\Video($video_file);
            $output_format = new \PHPVideoToolkit\VideoFormat();
            $output_format->setVideoDimensions(320, 240);
            $process = $video->save($video_out_file, $output_format, \PHPVideoToolkit\Video::OVERWRITE_EXISTING);

            // Extract Frame
            $image = new \PHPVideoToolkit\Video($video_file);
            $extract_time = ($total_seconds >= 5.0) ? 5 : (int)floor($total_seconds / 2.0);
            $image->extractFrame(new \PHPVideoToolkit\Timecode($extract_time));
            $output_format = new \PHPVideoToolkit\ImageFormat_Jpeg();
            $output_format->setVideoDimensions(320, 240);
            $iprocess = $image->save($image_out_file, $output_format);

            $a = [];
            $a['video_islem'] = $process->getRunTime();
            $a['video_sure'] = $data['duration']->getTimecode('%hh:%mm:%ss', true);
            $a['video_dosya'] = $video_out;
            $a['video_komut'] = $process->getExecutedCommand();
            $a['video_original'] = $videop;
            $a['resim_dosya'] = $image_out;
            $a['resim_komut'] = $iprocess->getExecutedCommand();
            $a['oke'] = true;
            $a['komut'] = $a['video_komut'];
            $a['mesaj'] = sprintf($this->lang['mod_video']['20'], $a['video_islem']);
            echo json_encode($a);
            exit;

        } catch (\Throwable $e) {
            // Graceful Fallback if FFMPEG execution fails, crashes or times out
            $custom_duration = $this->get_mp4_duration($video_file);
            $a = [];
            $a['video_islem'] = 0;
            $a['video_sure'] = $custom_duration;
            $a['video_dosya'] = $videop; // Use original uploaded file
            $a['video_komut'] = 'N/A';
            $a['video_original'] = $videop;
            $a['resim_dosya'] = ''; // No thumbnail generated
            $a['resim_komut'] = 'N/A';
            $a['oke'] = true;
            $a['komut'] = 'N/A';
            $a['mesaj'] = "Vebijarka paşvekişînê ya ewledar (graceful fallback) hate xebitandin. Vîdyo bêyî veguherandin hat qebûlkirin.";
            echo json_encode($a);
            exit;
        }
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

        $dir = root_dir . 'cache/video_tmp';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $hedef = $dir . '/' . $name;
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

        // Detect FFMPEG availability for frontend bypass
        $ffmpeg_available = false;
        $ffmpeg_path = '/usr/bin/ffmpeg';
        $ffprobe_path = '/usr/bin/ffprobe';
        $custom_ffmpeg = '/www/htdocs/w020f420/bin/ffmpeg';
        $custom_ffprobe = '/www/htdocs/w020f420/bin/ffprobe';
        $dir_parts = explode(DIRECTORY_SEPARATOR, root_dir);
        $htdocs_idx = array_search('htdocs', $dir_parts);
        if ($htdocs_idx !== false && isset($dir_parts[$htdocs_idx + 1])) {
            $user_dir = $dir_parts[$htdocs_idx + 1];
            $custom_ffmpeg = DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'htdocs' . DIRECTORY_SEPARATOR . $user_dir . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'ffmpeg';
            $custom_ffprobe = DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'htdocs' . DIRECTORY_SEPARATOR . $user_dir . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'ffprobe';
        }
        $local_ffmpeg = root_dir . 'bin' . DS . 'ffmpeg';
        $local_ffprobe = root_dir . 'bin' . DS . 'ffprobe';

        if (file_exists($custom_ffmpeg) && is_executable($custom_ffmpeg)) {
            $ffmpeg_path = $custom_ffmpeg;
        } elseif (file_exists($local_ffmpeg) && is_executable($local_ffmpeg)) {
            $ffmpeg_path = $local_ffmpeg;
        }

        if (file_exists($custom_ffprobe) && is_executable($custom_ffprobe)) {
            $ffprobe_path = $custom_ffprobe;
        } elseif (file_exists($local_ffprobe) && is_executable($local_ffprobe)) {
            $ffprobe_path = $local_ffprobe;
        }

        // Verify native binary existence and executability before instantiating PHPVideoToolkit
        $has_ffmpeg = false;
        $has_ffprobe = false;

        if (file_exists($ffmpeg_path) && is_executable($ffmpeg_path)) {
            $has_ffmpeg = true;
        } else {
            $global_check = @shell_exec('which ffmpeg 2>&1');
            if (!empty($global_check) && strpos($global_check, 'not found') === false && strpos($global_check, 'no ffmpeg') === false) {
                $has_ffmpeg = true;
            }
        }

        if (file_exists($ffprobe_path) && is_executable($ffprobe_path)) {
            $has_ffprobe = true;
        } else {
            $global_check = @shell_exec('which ffprobe 2>&1');
            if (!empty($global_check) && strpos($global_check, 'not found') === false && strpos($global_check, 'no ffprobe') === false) {
                $has_ffprobe = true;
            }
        }

        if ($has_ffmpeg && $has_ffprobe) {
            try {
                $tmp = root_dir . 'cache' . DS . 'video_tmp' . DS;
                $config = new \PHPVideoToolkit\Config([
                    'temp_directory' => $tmp,
                    'ffmpeg' => $ffmpeg_path,
                    'ffprobe' => $ffprobe_path,
                ], true);
                $ffmpeg = new \PHPVideoToolkit\FfmpegParser();
                $ffmpeg_available = $ffmpeg->isAvailable();
            } catch (\Throwable $e) {
                $ffmpeg_available = false;
            }
        } else {
            $ffmpeg_available = false;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_video']['add'];
        $this->tema->tpl('mod_video/add');
        $this->tema->veri('site', $this->site);
        $this->tema->veri('ffmpeg_available', $ffmpeg_available ? 'true' : 'false');
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
        $this->val = Load::validator('mod_video.add');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_video_model = Load::model('mod_video');

            $this->mod_video_model->lang = Req::Post_Safe('lang');
            $this->mod_video_model->name = Req::Post_Safe('name');
            $this->mod_video_model->duration = Req::Post_Safe('duration');
            $this->mod_video_model->created_at = date('Y-m-d H:i:s');

            $this->mod_video_model->status = Req::Post_Safe('status');

            $this->mod_video_model->description = Req::Post_Safe('description');

            $this->mod_video_model->acl_id = Req::Post_Safe('acl_id');
            $this->mod_video_model->user_id = $this->session->get('id');

            $this->mod_video_model->flv = basename(Req::Post_Safe('flv'));
            $this->mod_video_model->image = basename(Req::Post_Safe('image'));

            $vid = basename(Req::Post_Safe('video_original'));
            $cache_flv = root_dir . 'cache' . DS . 'video_tmp' . DS . $this->mod_video_model->flv;
            $cache_image = root_dir . 'cache' . DS . 'video_tmp' . DS . $this->mod_video_model->image;
            $cache_video = root_dir . 'cache' . DS . 'video_tmp' . DS . $vid;

            $real_flv_dir = root_dir . 'static' . DS . 'mod_video' . DS . 'flv';
            $real_image_dir = root_dir . 'static' . DS . 'mod_video' . DS . 'image';
            $real_video_dir = root_dir . 'static' . DS . 'mod_video' . DS . 'original';

            if (!is_dir($real_flv_dir)) {
                @mkdir($real_flv_dir, 0777, true);
            }
            if (!is_dir($real_image_dir)) {
                @mkdir($real_image_dir, 0777, true);
            }
            if (!is_dir($real_video_dir)) {
                @mkdir($real_video_dir, 0777, true);
            }

            $real_flv = $real_flv_dir . DS . $this->mod_video_model->flv;
            $real_image = $real_image_dir . DS . $this->mod_video_model->image;
            $real_video = $real_video_dir . DS . $vid;


            $uzanti = Helper::extension($vid);
            $valid_array = ['mov', 'mpeg', 'mpg', 'mp4', 'flv', 'wmv', 'avi'];
            if (!in_array($uzanti, $valid_array)) {
                @unlink($cache_flv);
                @unlink($cache_image);
                @unlink($cache_video);
                return $this->_error(print_r($_POST, TRUE) . sprintf($this->lang['mod_video']['errors']['in_valid_ext_original'], implode(',', $valid_array)));
            }


            // Move converted video to static directory
            if (copy($cache_flv, $real_flv) === FALSE) {
                @unlink($cache_flv);
                @unlink($cache_image);
                @unlink($cache_video);
                return $this->_error($this->lang['mod_video']['errors']['1']);
            }

            // Move image to static directory if it exists
            if (!empty($this->mod_video_model->image)) {
                if (copy($cache_image, $real_image) === FALSE) {
                    @unlink($cache_flv);
                    @unlink($cache_image);
                    @unlink($cache_video);
                    @unlink($real_flv);
                    return $this->_error($this->lang['mod_video']['errors']['2']);
                }
                @unlink($cache_image);
            }


            // Save original video if requested
            if (Req::Post_Safe('unremove_original') === 'y') {
                if (copy($cache_video, $real_video) === FALSE) {
                    @unlink($cache_flv);
                    @unlink($cache_image);
                    @unlink($cache_video);
                    @unlink($real_flv);
                    @unlink($real_image);
                    return $this->_error($this->lang['mod_video']['errors']['3']);
                }
                $this->mod_video_model->original = $vid;
            } else {
                $this->mod_video_model->original = NULL;
            }
            // Remove cache files
            @unlink($cache_video);
            @unlink($cache_flv);


            if ($this->mod_video_model->save(TRUE)) {
                $this->_oke_message($this->lang['mod_video']['message']['oke']['add']);
            } else {
                @unlink($real_image);
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
            $this->mod_video_model->duration = Req::Post_Safe('duration');
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
                // If image exists
                if (isset($image->url)) {
                    $image->_rollback();
                }
                $this->_error_message($this->lang['mod_video']['message']['error']['edit']);
            }
        }
    }

    // AJAX image uploader from the card layout.
    public function ajax_image_upload()
    {
        // Check edit permission.
        if ($this->user->hasPermission('mod_video', 'edit', $this->lang['mod_video']['role_errors']['edit'], 1) === False) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video']['ajax']['permission_denied']]);
            return;
        }

        $id = intval(Req::Post_Safe('id'));
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video']['ajax']['id_missing']]);
            return;
        }

        $this->mod_video_model = Load::model('mod_video');
        $this->mod_video_model->where_id($id)->gRo();
        if ($this->mod_video_model->getSize() === 0) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video']['errors']['record']]);
            return;
        }
        $this->mod_video_model->sync();

        // Check moderate permission if content belongs to another user.
        if ($this->user->checkPermission('mod_video', 'moderate_edit', $this->mod_video_model->user_id, $this->lang['mod_video']['role_errors']['moderate_edit']) === False) {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video']['ajax']['content_permission_denied']]);
            return;
        }

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

            try {
                if ($image->_resize($option)) {
                    $new_image_url = $image->url;
                    // Delete the old image before saving the new one, if it exists.
                    if ($this->mod_video_model->image != '') {
                        $image->mod = 'mod_video';
                        $image->url = $this->mod_video_model->image;
                        $image->_delete_files($option);
                    }

                    $update_model = Load::model('mod_video');
                    $update_model->id = $id;
                    $update_model->image = $new_image_url;

                    if ($update_model->update('id')) {
                        echo json_encode([
                            'status' => 'success',
                            'url' => $this->site['radres'] . 'mod_video/image/' . $new_image_url . '?v=' . time()
                        ]);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video']['ajax']['db_update_failed']]);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video']['errors']['image_upload_failed']]);
                }
            } catch (Throwable $e) {
                echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video']['errors']['image_upload_failed']]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => $this->lang['mod_video']['ajax']['file_not_uploaded']]);
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


    function delete()
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

    public function _delete($delete_ids)
    {
        $this->mod_video_model = Load::model('mod_video');
        foreach ($delete_ids as $s) {
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


            'y' => ['success', $this->lang['mod_video']['admin']['radio']['publish_yes']],


            'n' => ['danger', $this->lang['mod_video']['admin']['radio']['publish_no']],


        ];


        if ($getCount > 0) {
            foreach ($i->toArray() as $content) {

                $last = "";
                if (Roll::hasPermission('mod_video', 'edit')) {
                    $last .= '<button onclick="window.location.href=\''. $this->site['sadres'] .'mod_video/edit/' . $content['id'] . '\';return false;" class="btn default btn-xs purple">
    <i class="fa fa-edit"></i> ' . $this->lang['mod_video']['button']['edit'] . ' </button>';
                }
                if (Roll::hasPermission('mod_video',  'delete')) {
                    $last .= '<button onclick="VideoManager.processDelete(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs red">
    <i class="fa fa-trash-o"></i> ' . $this->lang['mod_video']['button'][ 'delete'] . ' </button>';
                }

                if (Roll::hasPermission('mod_video', 'gallery')) {
                    $last .= '<button onclick="VideoManager.processGallery(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs blue">
    <i class="fa fa-file-image-o"></i> ' . $this->lang['mod_video']['button']['gallery'] . ' </button>';
                }

                if (Roll::hasPermission('mod_video', 'file')) {
                    $last .= '<button onclick="VideoManager.processDosya(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs green">
    <i class="fa fa-file-zip-o"></i> ' . $this->lang['mod_video']['button']['file'] . ' </button>';
                }
                $data[] = [
                    '<input type="checkbox" name="id[]" value="' . $content['id'] . '">',
                    $content['id'],
                    $content['name'],
                    $content['image'] ? '<img src="' . $this->site['radres'] . 'mod_video/image/' . $content['image'] . '?v=' . time() . '" style="width: 50px; height: 35px; object-fit: cover; border-radius: 4px; border: 1px solid var(--md-sys-color-outline-variant);">' : '<span style="display: inline-block; width: 50px; height: 35px; background: rgba(0,0,0,0.05); border-radius: 4px; border: 1px solid rgba(0,0,0,0.08); text-align: center; line-height: 35px; color: #999;"><i class="fa fa-video-camera"></i></span>',
                    $content['duration'],
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
            // 'customActionMessage'=>'Is it ok now?'
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

    /**
     * Natively read MP4 file metadata headers to extract duration.
     * Parses atoms sequentially and skips mdat body to ensure sub-millisecond execution.
     */
    private function get_mp4_duration($file)
    {
        if (!is_file($file) || !is_readable($file)) {
            return '00:00:00';
        }
        $fp = fopen($file, 'rb');
        if (!$fp) {
            return '00:00:00';
        }

        $duration = 0;
        $time_scale = 0;

        while (!feof($fp)) {
            $atom_size_bin = fread($fp, 4);
            if (strlen($atom_size_bin) < 4) {
                break;
            }
            $atom_size = unpack('Nsize', $atom_size_bin)['size'];
            $atom_type = fread($fp, 4);
            if (strlen($atom_type) < 4) {
                break;
            }

            if ($atom_type === 'moov') {
                // Container atom, read inside it
                continue;
            }

            if ($atom_type === 'mvhd') {
                $version = ord(fread($fp, 1));
                fread($fp, 3); // flags

                if ($version === 1) {
                    fread($fp, 16); // creation and modification time (64-bit)
                    $time_scale_bin = fread($fp, 4);
                    $duration_bin = fread($fp, 8);
                    if (strlen($time_scale_bin) === 4 && strlen($duration_bin) === 8) {
                        $time_scale = unpack('Nscale', $time_scale_bin)['scale'];
                        // Read lower 32-bit of the 64-bit duration for safety across PHP versions
                        $duration_low = unpack('Nlow', substr($duration_bin, 4, 4))['low'];
                        $duration = $duration_low;
                    }
                } else {
                    fread($fp, 8); // creation and modification time (32-bit)
                    $time_scale_bin = fread($fp, 4);
                    $duration_bin = fread($fp, 4);
                    if (strlen($time_scale_bin) === 4 && strlen($duration_bin) === 4) {
                        $time_scale = unpack('Nscale', $time_scale_bin)['scale'];
                        $duration = unpack('Ndur', $duration_bin)['dur'];
                    }
                }
                break;
            }

            // Skip atom payload safely
            if ($atom_size > 8) {
                fseek($fp, $atom_size - 8, SEEK_CUR);
            } else {
                break;
            }
        }
        fclose($fp);

        if ($time_scale > 0 && $duration > 0) {
            $seconds = round($duration / $time_scale);
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            $secs = $seconds % 60;
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        }

        return '00:00:00';
    }
}

