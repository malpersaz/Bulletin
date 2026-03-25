<?php
/* AKM Software
 * @2010
 * Attachment Module
 * mod/mod_attachment/index.php
 */
class mod_attachment_mod extends indexMain
{
    public $mlang;
    public $mod_attachment_model;
    public $r;
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
        $this->lang = Load::lang('mod_attachment');
        // Dil Dosyasını Temaya Gönderelim
        $this->tema->lang($this->lang);

        if (Req::is_Post('sid')) {
            Kayit::ekle('session_sid', Req::Post_Safe('sid'));
        }
        $this->session = Load::lib('Session');
        // User Model
        $this->user = Load::model('mod_user.user');
    }

    public function index()
    {
        echo 'Dosya Modülü';
    }

    private function _resolve_record_title($record, $configuredValue = NULL)
    {
        if (!is_array($record) || empty($record) || $configuredValue === NULL || $configuredValue === '') {
            return FALSE;
        }

        if (!array_key_exists($configuredValue, $record)) {
            return FALSE;
        }

        $value = trim((string) $record[$configuredValue]);
        if ($value === '') {
            return FALSE;
        }

        // The configured display field must not point to a technical or relation column.
        if ($configuredValue === 'id' || substr($configuredValue, -3) === '_id') {
            return FALSE;
        }

        return $value;
    }


    public function _check_mod($mod, $vid)
    {
        if ($mod === NULL) {
            exit($this->lang['mod_attachment']['errors']['missing_module_name']);
            return FALSE;
        }


        if ($vid === NULL) {
            exit($this->lang['mod_attachment']['errors']['missing_record_id']);
            return FALSE;
        }
        // Modül varmı ve dosya Ekleniyormu
        $this->mod = Load::model('mod_attachment_mod');
        if ($this->mod->exists_mod_name($mod) === FALSE) {
            exit($this->lang['mod_attachment']['errors']['invalid_module']);
            return FALSE;
        }


        $this->mod->getResultOne();
        $this->mod->sync();
        $key = $this->mod->mod_key;
        $value = $this->mod->mod_value;

        $this->mlang = Load::lang($mod);
        // Dil Dosyası Varmı
        if (empty($this->mlang[$mod]['role_errors']['file'])) {
            exit($this->lang['mod_attachment']['errors']['module_file_permission_missing']);
        }

        // dosya Ekleme Permissionsi varmı?
        if ($this->user->hasPermission($mod, 'file', $this->mlang[$mod]['role_errors']['file']) === False) {
            exit($this->mlang[$mod]['role_errors']['file']);
            return False;
        }

        $this->model = Load::model($mod);
        if ($this->model->exists_id($vid) === FALSE) {
            Helper::yonlendir($this->site['adres'] . $mod . '/list', 1);
            print($this->lang['mod_attachment']['errors']['invalid_record']);
            return FALSE;
        }
        $this->model->getResultOne();
        $this->model->sync();

        $this->tema->veri('key', $key);
        $this->tema->veri('value', $value);

        $this->tema->veri('mod_name', $mod);

        $this->mod_attachment_model = Load::model('mod_attachment');
        $this->mod_attachment_model->where_vid_and_mod_name($vid, $mod)->gR();

        $record = $this->model->toArray();
        $record_title = $this->_resolve_record_title($record, $value);
        if ($record_title === FALSE) {
            exit($this->lang['mod_attachment']['errors']['invalid_record_title_field']);
        }

        $this->tema->veri('d', $record);
        $this->tema->veri('record_title', $record_title);
        $this->tema->veri('vid', $vid);
    }

    // Resim Listesi
    public function list($mod = NULL, $vid = NULL)
    {
        $mod = Req::Url_Clear($mod);
        $vid = Req::Url_Clear($vid);

        if ($mod === '') {
            $mod = NULL;
        }
        if ($vid === '') {
            $vid = NULL;
        }

        if ($mod === NULL && $vid === NULL) {
            if ($this->user->hasPermission('mod_attachment', 'list', $this->lang['mod_attachment']['role_errors']['list']) === False) {
                return False;
            }

            Run::import('management', '_index');
            $this->site['title'] = $this->lang['mod_attachment']['admin']['14'];
            $this->tema->veri('site', $this->site);
            $this->tema->veri('is_global_list', TRUE);

            $this->mod_attachment_model = Load::model('mod_attachment');
            $this->mod_attachment_model->select('id,filename,url,ext,original_name,type,created_at,mod_name,vid,user_id')->gR();

            if ($this->mod_attachment_model->getSize() !== 0) {
                $this->tema->veri('dosyalar', $this->mod_attachment_model->toArray());
            }

            $this->tema->tpl('mod_attachment/list');
            return TRUE;
        }

        if ($this->_check_mod($mod, $vid) === FALSE) {
            return FALSE;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_attachment']['admin']['14'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('is_global_list', FALSE);


        if ($this->mod_attachment_model->getSize() !== 0) {
            $this->tema->veri('dosyalar', $this->mod_attachment_model->toArray());
        }


        $this->tema->tpl('mod_attachment/list');
    }

    public function edit()
    {
        $vid = Req::Post_Safe('vid');
        $mod = Req::Post_Safe('mod_name');
        if ($this->_check_mod($mod, $vid) === FALSE) {
            return FALSE;
        }

        $this->r = Load::model('mod_attachment');

        $id = Req::Post_Safe('id');
        $this->r->where_id($id)->gRo();
        if ($this->r->getSize() === 0) {
            exit($this->lang['mod_attachment']['errors']['invalid_file']);
        }
        $this->r->id = $id;
        $this->r->filename = Req::Post_Safe('name');
        $this->r->update('id');
        echo 'Oke';
    }

    public function addegis()
    {
        return $this->edit();
    }

    public function delete()
    {

        $vid = Req::Post_Safe('vid');
        $mod = Req::Post_Safe('mod_name');
        if ($this->_check_mod($mod, $vid) === FALSE) {
            return FALSE;
        }

        $file_model = Load::model('mod_attachment');
        $id = Req::Post_Safe('id');
        $file_model->where_id_and_vid_and_mod_name($id, $vid, $mod)->limit(1)->gRo();
        if ($file_model->getSize() === 0) {
            exit($this->lang['mod_attachment']['errors']['invalid_file']);
        }

        $file_model->sync();


        $file = Load::mod('file');
        $file->mod = $mod;
        $file->url = $file_model->url;

        if ($file->_delete() and $file_model->delete()) {
            echo 'Oke';
        } else {
            echo $file->error;
        }
    }

    public function sil()
    {
        return $this->delete();
    }

    public function add()
    {
        $vid = Req::Post_Safe('vid');
        $mod = Req::Post_Safe('mod_name');
        if ($this->_check_mod($mod, $vid) === FALSE) {
            return FALSE;
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_attachment']['admin']['1'];

        $this->tema->veri('site', $this->site);
        $this->tema->veri('vid', $vid);
        $this->tema->tpl('mod_attachment/add');
    }


    public function yukle()
    {
        return $this->add();
    }

    public function upload()
    {
        $isim = Req::Post_Safe('isim');


        if (Req::is_Files($isim) == False) {
            exit('Hata1');
        }

        $vid = Req::Post_Safe('vid');
        $mod = basename(Req::Post_Safe('mod_name'));


        if ($this->_check_mod($mod, $vid) === FALSE) {
            return FALSE;
        }


        $valid_ext = ['txt', 'xls', 'jpg', 'png', 'gif', 'png', 'bmp', 'jpeg', 'JPG', 'JPEG', 'PNG', 'GIF', 'rar', 'zip', 'pdf', 'doc', 'docx', 'ods', 'ppt', 'odt', 'epub', 'RAR', 'ZIP', 'PDF', 'DOC', 'DOCX', 'ODS', 'PPT', 'ODT', 'EPUB'];


        // Eğer Resim varsa
        if (Req::is_Files($isim) && Req::Files_Name($isim) != '') {
            $file = Load::mod('file');
            $file->mod = $mod;
            $file->name = $isim;

            $option = [
                'valid_ext' => $valid_ext
            ];


            if ($file->_upload($option)) {
                $result = $file->result;
            } else {
                echo $file->error;
                return FALSE;
            }
        } else {
            exit('Dosya Yok Ortalıkta ' . print_r($_FILES, TRUE));
        }


        $file_model = Load::model('mod_attachment');
        $file_model->filename = Req::Clear($result['original_name']);
        $file_model->url = $result['url'];
        $file_model->ext = $result['ext'];
        $file_model->original_name = $result['original_name'];
        $file_model->type = $result['type'];
        $file_model->mod_name = $mod;
        $file_model->vid = $vid;
        $file_model->user_id = $this->session->getir('id');

        if ($file_model->save()) {
            echo $result['original_name'];
        } else {
            echo 'ERROR';
        }
    }

    public function up()
    {
        return $this->upload();
    }

    // Valid Mesajı
    private function _mesaj($mesaj)
    {
        $this->tema->veri('oke', True);
        $this->tema->veri('mesaj', $mesaj);
    }

    // Hata Mesajı
    private function _emesaj($mesaj)
    {
        $this->tema->veri('error', True);
        $this->tema->veri('mesaj', $mesaj);
    }


    public function _remove($mod, $vid = NULL)
    {

        $this->r = Load::model('mod_attachment');

        if ($vid == NULL) {
            $this->r->where_mod_name($mod)->gR();
        } else {
            $this->r->where_vid_and_mod_name($vid, $mod)->gR();
        }
        //$id = Req::Post_Safe('id');

        // GALLERY YOK DEMEK
        if ($this->r->getSize() === 0) {
            return TRUE;
        }
        // Galleri varsa eğer
        $filelar = $this->r->toArray();

        foreach ($filelar as $d) {


            $file = Load::mod('file');
            $file->mod = $mod;
            $file->url = $d['url'];

            if ($file->_delete()) {
                continue;
            }
            echo $file->error;
            return FALSE;
        }
        // Veritabanından'danda Silelim
        $this->r->delete();
    }
}
