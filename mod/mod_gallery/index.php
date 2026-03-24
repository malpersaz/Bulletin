<?php
/* AKM Software
 * @2010
 * Gallery Module
 * mod/mod_gallery/index.php
 */
class mod_gallery_mod extends indexMain
{
    public $mlang;
    public $mod_gallery_model;
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
        $this->lang = Load::lang('mod_gallery');
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
        echo 'Images Module';
    }


    public function _check_mod($mod, $vid)
    {
        if ($mod === NULL) {
            print(":");
            return FALSE;
        }


        if ($vid === NULL) {
            print("::");
            return FALSE;
        }
        // Modül varmı ve Gallery Ekleniyormu
        $this->mod = Load::model('mod_gallery_mod');
        if ($this->mod->exists_mod_name($mod) === FALSE) {
            print(":::");
            return FALSE;
        }


        $this->mod->getResultOne();
        $this->mod->sync();
        $key = $this->mod->mod_key;
        $value = $this->mod->mod_value;

        $this->mlang = Load::lang($mod);
        // Dil Dosyası Varmı
        if (empty($this->mlang[$mod]['role_errors']['gallery'])) {
            exit("..");
        }

        // Gallery Ekleme Permissionsi varmı?
        if ($this->user->hasPermission($mod, 'gallery', $this->mlang[$mod]['role_errors']['gallery']) === False) {
            return False;
        }

        $this->model = Load::model($mod);
        if ($this->model->exists_id($vid) === FALSE) {
            Helper::yonlendir($this->site['adres'] . $mod . '/list', 1);
            print("::::");
            return FALSE;
        }
        $this->model->getResultOne();
        $this->model->sync();

        $this->tema->veri('key', $key);
        $this->tema->veri('value', $value);

        $this->tema->veri('mod_name', $mod);

        $this->mod_gallery_model = Load::model('mod_gallery');
        $this->mod_gallery_model->where_vid_and_mod_name($vid, $mod)->gR();

        $this->tema->veri('d', $this->model->toArray());
        $this->tema->veri('vid', $vid);
    }

    // Resim Listesi
    public function list($mod = NULL, $vid = NULL)
    {
        $mod = Req::Url_Clear($mod);
        $vid = Req::Url_Clear($vid);

        if ($this->_check_mod($mod, $vid) === FALSE) {
            exit(".");
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_gallery']['admin']['14'];
        $this->tema->veri('site', $this->site);


        if ($this->mod_gallery_model->getSize() !== 0) {
            $this->tema->veri('pictures', $this->mod_gallery_model->toArray());
        }


        $this->tema->tpl('mod_gallery/list');
    }

    public function addegis()
    {
        $vid = Req::Post_Safe('vid');
        $mod = Req::Post_Safe('mod_name');
        if ($this->_check_mod($mod, $vid) === FALSE) {
            exit(".");
        }

        $this->r = Load::model('mod_gallery');

        $id = Req::Post_Safe('id');
        $this->r->where_id($id)->gRo();
        if ($this->r->getSize() === 0) {
            exit("..");
        }
        $this->r->id = $id;
        $this->r->name = Req::Post_Safe('name');
        $this->r->update('id');
        echo 'Oke';
    }

    public function sil()
    {

        $vid = Req::Post_Safe('vid');
        $mod = Req::Post_Safe('mod_name');
        if ($this->_check_mod($mod, $vid) === FALSE) {
            exit(".");
        }

        $this->r = Load::model('mod_gallery');
        $id = Req::Post_Safe('id');
        $this->r->where_id_and_vid_and_mod_name($id, $vid, $mod)->limit(1)->gRo();
        if ($this->r->getSize() === 0) {
            exit("....");
        }

        $this->r->sync();


        $image = Load::mod('image');
        $image->mod = 'mod_gallery';
        $image->url = $this->r->url;
        $option = [
            '/' . $mod . '/original/' =>
            [
                'width' => NULL,
                'height' => NULL,
                'option' => 'original'
            ],
            '/' . $mod . '/thumb' =>
            [
                'width' => 250,
                'height' => NULL,
                'option' => 'width',
            ]
        ];
        if ($image->_delete_files($option) and $this->r->delete()) {
            echo 'Oke';
            return TRUE;
        }
        echo $image->error;
        return FALSE;
    }

    public function yukle()
    {
        $vid = Req::Post_Safe('vid');
        $mod = Req::Post_Safe('mod_name');
        if ($this->_check_mod($mod, $vid) === FALSE) {
            exit(".");
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_gallery']['admin']['1'];

        $this->tema->veri('site', $this->site);
        $this->tema->veri('vid', $vid);
        $this->tema->tpl('mod_gallery/yukle');
    }


    public function up()
    {
        $isim = Req::Post_Safe('isim');


        if (Req::is_Files($isim) == False) {
            exit('Hata1');
        }

        $vid = Req::Post_Safe('vid');
        $mod = Req::Post_Safe('mod_name');


        if ($this->_check_mod($mod, $vid) === FALSE) {
            exit(".");
        }

        // Eğer Resim varsa
        if (isset($_FILES[$isim]['name']) and  $_FILES[$isim]['name'] != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_gallery';
            $image->name = $isim;
            $option = [
                '/' . $mod . '/original/' =>
                [
                    'width' => NULL,
                    'height' => NULL,
                    'option' => 'original'
                ],
                '/' . $mod . '/thumb' =>
                [
                    'width' => 250,
                    'height' => NULL,
                    'option' => 'width',
                ]
            ];
            if ($image->_resize($option)) {
                $resim_link = $image->url;
            } else {
                exit($image->error);
                return FALSE;
            }
        } else {
            exit('Resim YOk ORTALIKTA ' . print_r($_FILES, TRUE));
        }


        $this->r = Load::model('mod_gallery');
        //$this->r->name = $this->image->isim;
        $this->r->name = "#";
        $this->r->url = $resim_link;
        $this->r->mod_name = $mod;
        $this->r->vid = $vid;
        $this->r->user_id = $this->session->getir('id');

        if ($this->r->save()) {
            echo 'static/mod_gallery/' . $mod . DS . 'thumb' . DS . $resim_link;
        } else {
            echo 'ERROR';
        }
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
        $this->tema->veri('hata', True);
        $this->tema->veri('mesaj', $mesaj);
    }


    public function _remove($mod, $vid = NULL)
    {

        $this->r = Load::model('mod_gallery');

        if ($vid == NULL) {
            $this->r->where_mod_name($mod)->gR();
        } else {
            $this->r->where_vid_and_mod_name($vid, $mod)->gR();
        }

        // GALLERY YOK DEMEK
        if ($this->r->getSize() === 0) {
            return TRUE;
        }
        // Galleri varsa eğer
        $gallerys = $this->r->toArray();

        foreach ($gallerys as $g) {


            $image = Load::mod('image');
            $image->mod = 'mod_gallery';
            $image->url = $g['url'];
            $option = [
                '/' . $mod . '/original/' =>
                [
                    'width' => NULL,
                    'height' => NULL,
                    'option' => 'original'
                ],
                '/' . $mod . '/thumb' =>
                [
                    'width' => 250,
                    'height' => NULL,
                    'option' => 'width',
                ]
            ];
            if ($image->_delete_files($option)) {
                continue;
            }
            echo $image->error;
            return FALSE;
        }
        // Veritabanından'danda Silelim
        $this->r->delete();
    }
}
