<?php

/* Akm Yazılım
 * @2010
 * Üyelik Modülü
 * mod/mod_user/index.php
 */

class mod_user_mod
{
    function __construct()
    {
        // Tema Kütüphanesini Çağıralım
        $this->tema = Load::lib('AdminTemplate');

        $this->apptemplate = Load::lib('Template');
        // Database Kütüphanesini Yükleyelim
        //$this->db = Load::lib('Database');
        // Site ayarlarını çekelim
        $this->site = Kayit::getir('site_config');
        // Dil Dosyasını Çağıralım
        $this->lang = Load::lang('mod_user');
        // Dil Dosyasını Temaya Gönderelim
        $this->tema->lang($this->lang);
        $this->apptemplate->lang($this->lang);
        // User Model
        $this->user = Load::model('mod_user.user');
        // Session Kütüphanesi
        $this->ses = Load::lib('Session');
    }

// Hesap Aktifleştirir
    public function durum()
    {
        // Düzenleme Yetkisi varmı?
        if ($this->user->yetki('mod_user', 'duzenle', $this->lang ['mod_user'] ['rhata'] ['duzenle'], 1) === False) {
            return False;
        }
        $sil = Req::Post_Safe('query');
        return $this->_durum($sil);
    }

// Hesap Aktifleştirir
    private function _durum($sil = NULL)
    {
        // mod_user Modeli Yükleyelim
        $this->model = Load::model('mod_user');
        if (is_array($sil)) {
            foreach ($sil as $s) {
                $this->model->Getid($s)->getResultOne(TRUE, TRUE)->sync();
                if ($this->model->durum != 'e') {
                    $this->model->id = $s;
                    $durum = $this->model->durum;
                    $this->model->durum = 'e';
                    if ($this->model->update('id')) {
                        if($durum=='a'){
                            $this->_yonetim_akfilestime_email_at();
                        }
                    }
                }
            }
            echo 'Oke';
        }
    }



    // Hesap Aktifleştirir
    public function durumkaldir()
    {
        // Düzenleme Yetkisi varmı?
        if ($this->user->yetki('mod_user', 'duzenle', $this->lang ['mod_user'] ['rhata'] ['duzenle'], 1) === False) {
            return False;
        }
        $sil = Req::Post_Safe('query');
        return $this->_durumkaldir($sil);
    }

// Hesap Aktifleştirir
    private function _durumkaldir($sil = NULL)
    {
        // mod_user Modeli Yükleyelim
        $this->model = Load::model('mod_user');
        if (is_array($sil)) {
            foreach ($sil as $s) {
                $this->model->Getid($s)->getResultOne(TRUE, TRUE)->sync();
                if ($this->model->durum == 'e') {
                    $this->model->id = $s;
                    $this->model->durum = 'h';
                    $this->model->update('id');
                }

            }
            echo 'Oke';
        }
    }


// Hesap aktifleştirince Mail atan fonksiyon
    private function _yonetim_akfilestime_email_at()
    {
        $key = Helper::randomkey(20);
        $email = Load::lib('Email');
        $email->html = True;
        $email->kime = array($this->model->email => $this->model->ad . " " . $this->model->soyad);
        $email->konu = $this->lang ['mod_user'] ['aktivasyon'] ['8'];
        $this->apptemplate->veri('ad', $this->model->ad);
        $this->apptemplate->veri('soyad', $this->model->soyad);
        $this->apptemplate->veri('site', $this->site);
        $email->mesaj = $this->apptemplate->fetch('mod_user/pyonetici.tpl');
        if ($email->gonder()) {
            return True;
        } else {
            return False;
        }
    }

    // Giriş Sayfası
    public function giris()
    {
        Run::import('index', '_index');
        $d ['title'] = $this->lang ['mod_user'] ['4'];
        $this->apptemplate->veri('d', $d);
        //$this->apptemplate->veri('site', $this->site);
        $this->apptemplate->veri('tpl', 'mod_user/giris.tpl');

        // Giriş Yapmışmı?
        if ($this->_check_login()) {
            return;
        }

        // Refererri al
        $ref = isset($_SERVER['HTTP_REFERER']) ? Req::Clear($_SERVER ['HTTP_REFERER']) : '';
        // Eğer ref boşsa site anasayfasına yönlendir.
        $r = $ref != '' ? $ref : $this->site ['adres'] . 'mod_user/panel';
        if (Req::is_Post('giris')) {
            $this->val = Load::validator('mod_user.giris');
            if ($this->val->ekle()) {
                $this->apptemplate->veri('username', Req::Post_Safe('username'));
                $this->apptemplate->veri('password', Req::Post_Safe('password'));
                $this->apptemplate->veri('hatalar', $this->val->hatalar);
            } elseif ($this->user->giris(Req::Post_Safe('username'), Req::Post_Safe('password'))) {
                $this->_appmesaj($this->lang ['mod_user'] ['6']);
                Helper::yonlen($r, 3);
            } else {
                $this->apptemplate->veri('username', Req::Post_Safe('username'));
                $this->apptemplate->veri('password', Req::Post_Safe('password'));
                $this->apptemplate->veri('hatalar', $this->user->hatalar);
            }
        }

    }

    // Çıkış Sayfası
    public function cikis($sid = NULL)
    {
        // Url den gelen sid değeri validmi
        if ($sid === $this->ses->getir('sid')) {
            // Çıkış yap
            $this->user->cikis($sid);
            // Ref'i al
            $ref = isset($_SERVER['HTTP_REFERER']) ? Req::Clear($_SERVER ['HTTP_REFERER']) : '';
            // Eğer ref boşsa site anasayfasına yönlendir.
            $r = $ref != '' ? $ref : $this->site ['adres'];
            return Helper::yonlendir($r, 1);
        } else {
            exit('SSID geçersiz');
        }
    }

    public function sifremiunuttum()
    {
        // Eğer Üye İse panel'e yönlendir
        if ($this->_check_login()) {
            return;
        }

        Run::import('index', '_index');
        if (Req::is_Post('sifremiunuttum')) {
            $this->_sifremiunuttum();
        }
        $d ['title'] = $this->lang ['mod_user'] ['sifremiunuttum'] ['7'];
        $this->apptemplate->veri('d', $d);
        $this->captcha = Load::lib('Captcha');
        $this->apptemplate->veri('captcha_image', $this->captcha->resim());

        $this->apptemplate->veri('tpl', 'mod_user/sifremiunuttum.tpl');
    }

    private function _sifremiunuttum()
    {
        $this->val = Load::validator('mod_user.sifremiunuttum');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->apptemplate->veri($key, Req::Post_Safe($key));
            }
            $this->apptemplate->veri('hatalar', $this->val->hatalar);
        } else {
            $this->model = Load::model('mod_user');
            $this->model->Getemail(Req::Post_Safe('email'))->getResultOne(TRUE, TRUE)->sync();
            if ($this->_semail_at()) {
                $this->_appmesaj($this->lang ['mod_user'] ['sifremiunuttum'] ['1']);
            } else {
                $this->_appmesaj($this->lang ['mod_user'] ['sifremiunuttum'] ['hata'] ['1']);
            }

        }
    }

    private function _semail_at()
    {
        // Daha Önce Key Oluşturulmuşmu
        $this->saktivasyonmodel = Load::model('mod_user.saktivasyon');
        $this->saktivasyonmodel->getemail($this->model->email)->getResultOne(TRUE, TRUE);
        if ($this->saktivasyonmodel->getSize() !== 0) {
            $this->saktivasyonmodel->sync();
            $key = $this->saktivasyonmodel->kod;
        } else {
            $key = Helper::randomkey(20);
            $this->saktivasyonmodel->email = $this->model->email;
            $this->saktivasyonmodel->kod = $key;
            $this->saktivasyonmodel->save();
        }

        $email = Load::lib('Email');
        $email->html = True;
        $email->kime = array($this->model->email => $this->model->ad . " " . $this->model->soyad);
        $email->konu = $this->lang ['mod_user'] ['saktivasyon'] ['1'];
        $this->apptemplate->veri('ad', $this->model->ad);
        $this->apptemplate->veri('soyad', $this->model->soyad);
        $this->apptemplate->veri('kod', $key);
        $this->apptemplate->veri('site', $this->site);
        $this->apptemplate->veri('ip', $_SERVER ['REMOTE_ADDR']);
        $email->mesaj = $this->apptemplate->fetch('mod_user/sposta.tpl');
        if ($email->gonder()) {
            return TRUE;
        } else {
            return FALSE;
        }
    }


    public function saktivasyon($kod = NULL)
    {
        Run::import('index', '_index');
        if ($kod != 'NULL' and strlen($kod) == 20) {
            $this->apptemplate->veri('kod', Req::Url_Clear($kod));
        }
        if (Req::is_Post('saktivasyon')) {
            $this->_saktivasyon();
        }
        $d ['title'] = $this->lang ['mod_user'] ['saktivasyon'] ['1'];
        $this->apptemplate->veri('d', $d);

        $this->captcha = Load::lib('Captcha');
        $this->apptemplate->veri('captcha_image', $this->captcha->resim());

        $this->apptemplate->veri('tpl', 'mod_user/saktivasyon.tpl');

    }

    private function _saktivasyon()
    {
        $this->val = Load::validator('mod_user.saktivasyon');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->apptemplate->veri($key, Req::Post_Safe($key));
            }
            $this->apptemplate->veri('hatalar', $this->val->hatalar);
        } else {
            $this->saktivasyon = Load::model('mod_user.saktivasyon');
            $this->saktivasyon->Getkod(Req::Post_Safe('kod'))->gro()->sync();

            $this->model = Load::model('mod_user');
            $this->model->Getemail($this->saktivasyon->email)->gro()->sync();

            if ($this->model->email === '') {
                exit('Üyelik Silinmiş Olabilir');
            }

            $pass = Helper::randomkey(16);
            $this->model->password = md5($pass);


            if ($this->_email_pass($pass)) {
                if ($this->model->update('id')) {
                    $this->_appmesaj($this->lang ['mod_user'] ['saktivasyon'] ['6']);
                }
            } else {
                $this->_appmesaj($this->lang ['mod_user'] ['saktivasyon'] ['hata'] ['4']);
            }

        }
    }

    private function _email_pass($pass)
    {
        $email = Load::lib('Email');
        $email->html = True;
        $email->kime = array($this->model->email => $this->model->ad . " " . $this->model->soyad);
        $email->konu = $this->lang ['mod_user'] ['kayit'] ['17'];
        $this->apptemplate->veri('ad', $this->model->ad);
        $this->apptemplate->veri('soyad', $this->model->soyad);
        $this->apptemplate->veri('password', $pass);
        $this->apptemplate->veri('site', $this->site);
        $this->apptemplate->veri('ip', $_SERVER ['REMOTE_ADDR']);
        $email->mesaj = $this->apptemplate->fetch('mod_user/pposta.tpl');
        if ($email->gonder()) {
            $this->saktivasyon->delete();
            return True;
        } else {
            return False;
        }
    }

// Aktivasyon Kodunu Tekrar Gönderir
    public function re_aktivasyon()
    {
        Run::import('index', '_index');
        if (Req::is_Post('re_aktivasyon')) {
            $this->_re_aktivasyon();
        }
        $d ['title'] = $this->lang ['mod_user'] ['re_aktivasyon'] ['7'];
        $this->apptemplate->veri('d', $d);
        $this->captcha = Load::lib('Captcha');
        $this->apptemplate->veri('captcha_image', $this->captcha->resim());
        $this->apptemplate->veri('tpl', 'mod_user/re_aktivasyon.tpl');

    }

// Aktivasyon Kodunu Tekrar Gönderir
    private function _re_aktivasyon()
    {
        $this->val = Load::validator('mod_user.re_aktivasyon');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->apptemplate->veri($key, Req::Post_Safe($key));
            }
            $this->apptemplate->veri('hatalar', $this->val->hatalar);
        } else {
            $this->aktivasyon = Load::model('mod_user.aktivasyon');
            $this->aktivasyon->getemail(Req::Post_Safe('email'))->gRo();
            if ($this->aktivasyon->getSize() === 0) {
                $this->_appemesaj($this->lang ['mod_user'] ['re_aktivasyon']['hata'] ['8']);
                return FALSE;
            }
            $this->aktivasyon->sync();
            if ($this->_email_at($this->aktivasyon->kod)) {
                $this->_appmesaj($this->lang ['mod_user'] ['re_aktivasyon'] ['1']);
            } else {
                $this->_appemesaj($this->lang ['mod_user'] ['re_aktivasyon'] ['hata'] ['1']);
            }

        }
    }

// Aktivasyon Kodunu İçin Mail Gönder
    private function _email_at($kod = NULL)
    {
        if ($kod !== NULL) {
            $key = $kod;

        } else {
            $key = Helper::randomkey(20);
        }

        $this->model = Load::model('mod_user');
        $this->model->select('ad,soyad,email')->Getemail(Req::Post_Safe('email'))->gRo()->sync();

        $email = Load::lib('Email');
        $email->html = True;
        $email->kime = array($this->model->email => $this->model->ad . " " . $this->model->soyad);
        $email->konu = $this->lang ['mod_user'] ['kayit'] ['15'];
        $this->apptemplate->veri('ad', $this->model->ad);
        $this->apptemplate->veri('soyad', $this->model->soyad);
        $this->apptemplate->veri('kod', $key);
        $this->apptemplate->veri('site', $this->site);
        $email->mesaj = $this->apptemplate->fetch('mod_user/posta.tpl');
        if ($email->gonder()) {
            if ($kod === NULL) {
                $this->aktivasyon = Load::model('mod_user.aktivasyon');
                $this->aktivasyon->email = $this->model->email;
                $this->aktivasyon->kod = $key;
                $this->aktivasyon->save();
            }
            return TRUE;
            /*$this->ak = Load::model('mod_user.aktivasyon');
            $this->ak->delete_email($this->model->email);
            $this->ak->email = $this->model->email;
            $this->ak->kod = $key;
            if ($this->ak->save()) {
                return True;
            } else {
                return False;
            }*/
        } else {
            return False;
        }
    }

// Üyelik Aktivasyonu
    public function aktivasyon($kod = NULL)
    {
        Run::import('index', '_index');
        if ($kod != 'NULL' and strlen($kod) == 20) {
            $this->apptemplate->veri('kod', Req::Url_Clear($kod));
        }
        if (Req::is_Post('aktivasyon')) {
            $this->_aktivasyon();
        }
        $d ['title'] = $this->lang ['mod_user'] ['aktivasyon'] ['8'];
        $this->apptemplate->veri('d', $d);
        $this->captcha = Load::lib('Captcha');
        $this->apptemplate->veri('captcha_image', $this->captcha->resim());

        $this->apptemplate->veri('tpl', 'mod_user/aktivasyon.tpl');

    }

// Üyelik Aktivasyonu
    private function _aktivasyon()
    {
        $this->val = Load::validator('mod_user.aktivasyon');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->apptemplate->veri($key, Req::Post_Safe($key));
            }
            $this->apptemplate->veri('hatalar', $this->val->hatalar);
        } else {
            $this->m = Load::model('mod_user.aktivasyon');
            $this->m->Getkod(Req::Post_Safe('kod'))->gRo()->sync();
            $this->u = Load::model('mod_user');
            $this->u->Getemail($this->m->email)->gRo()->sync();
            $this->u->durum = 'e';
            if ($this->u->update('id')) {
                $this->m->delete();
                $this->_appmesaj($this->lang ['mod_user'] ['aktivasyon'] ['7']);
            }
        }
    }

// Üyelik Kaydı
    public function kayit()
    {
        // Giriş Yapmışmı?
        if ($this->_check_login()) {
            return;
        }

        Run::import('index', '_index');
        $this->apptemplate->veri('tpl', 'mod_user/kayit.tpl');
        //echo  date("Y-m-d H:i:s",time());
        $this->ayar = Load::model('mod_user.mod_user_ayar');
        $this->ayar->all();

        foreach ($this->ayar->toArray() as $key => $val) {
            $d [$val ['k']] = $val ['v'];
        }
        if ($d ['durum'] == '0') {
            $this->_appemesaj($this->lang ['mod_user'] ['kayit'] ['1']);
            $d ['title'] = $this->lang ['mod_user'] ['kayit'] ['18'];
            return FALSE;
        }

        $this->_aktivasyon = $d ['aktivasyon'];
        $this->_group_id = $d ['group_id'];

        if (Req::is_Post('kayit')) {
            $this->_kayit();
        }
        //unset ($d);
        $d ['title'] = $this->lang ['mod_user'] ['kayit'] ['18'];
        //$this->apptemplate->veri('d', $d);
        $this->captcha = Load::lib('Captcha');
        $this->apptemplate->veri('captcha_image', $this->captcha->resim());
        $this->apptemplate->veri('tpl', 'mod_user/kayit.tpl');

    }


    private function _kayit()
    {
        $this->val = Load::validator('mod_user.kayit');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->apptemplate->veri($key, Req::Post_Safe($key));
            }
            $this->apptemplate->veri('hatalar', $this->val->hatalar);
        } else {
            $this->apptemplate->veri('oke', True);

            $this->model = Load::model('mod_user');
            $this->model->ad = Text::title(Req::Post_Safe('ad'));
            $this->model->soyad = Text::title(Req::Post_Safe('soyad'));
            $this->model->username = Req::Post_Safe('username');
            $this->model->email = Req::Post_Safe('email');
            $this->model->password = md5(Req::Post_Safe('password'));
            $this->model->cep = Req::Post_Safe('cep');
            $this->model->group_id = $this->_group_id;
            if ($this->_aktivasyon == 1) {
                $this->model->durum = 'h';
            } elseif ($this->_aktivasyon == 2) {
                $this->model->durum = 'a';
            } else {
                $this->model->durum = 'e';
            }
            if ($this->model->save()) {
                if ($this->_aktivasyon == 1) {
                    if ($this->_email_at()) {
                        $this->apptemplate->veri('mesaj', $this->lang ['mod_user'] ['kayit'] ['14']);
                    } else {
                        $this->apptemplate->veri('mesaj', $this->lang ['mod_user'] ['kayit'] ['hata'] ['16']);
                    }
                } elseif ($this->_aktivasyon == 2) {
                    $this->apptemplate->veri('mesaj', $this->lang ['mod_user'] ['kayit'] ['19']);
                } else {
                    $this->apptemplate->veri('mesaj', $this->lang ['mod_user'] ['kayit'] ['13']);
                }
            } else {
                exit ($this->model->db->sql_error());
            }

        }
    }

    public function ayar()
    {

        // Listeleme Yetkisi varmı?
        if ($this->user->yetki('mod_user', 'ayar', $this->lang ['mod_user'] ['rhata'] ['ayar'], 1) === False) {
            return False;
        }
        Run::import('yonetim', '_index');

        if (Req::is_Post('duzenle')) {
            $this->_ayar($_POST);
        }
        $this->ayar = Load::model('mod_user.mod_user_ayar');

        $sonuc = $this->ayar->all()->toArray();

        foreach ($sonuc as $key => $val) {
            $d [$val ['k']] = $val ['v'];
        }
        // Üye Grubunu çekelim
        $g = Load::model('mod_user_group');
        $group = Helper::nested($g->all()->toArray(), 'alt', 'group_id');


        $durum = $d ['durum'];
        $aktivasyon = $d ['aktivasyon'];
        $check_user_login = $d['check_user_login'];
        $this->site ['title'] = $this->lang ['mod_user'] ['admin'] ['14'];
        $this->tema->veri('tpl', 'mod_user/ayar.tpl');
        $this->tema->veri('site', $this->site);
        $this->tema->veri('group', $group);
        $this->tema->veri('durum', $durum);
        $this->tema->veri('check_user_login', $check_user_login);
        $this->tema->veri('d', $d);
        $this->tema->veri('aktivasyon', $aktivasyon);

    }

    private function _ayar($array)
    {
        $array = Req::Clear($array);
        $this->ayar = Load::model('mod_user.mod_user_ayar');
        if (is_array($array)) {
            foreach ($array as $key => $val) {
                if ($key != 'duzenle') {
                    $this->ayar->k = $key;
                    $this->ayar->v = $val;
                    if ($this->ayar->update('k') === False) {
                        print_r($this->ayar);
                        $this->emesaj($this->ayar->db->sql_error());
                        return False;
                    }
                }

            }
        }
        $this->_mesaj($this->lang ['mod_user'] ['admin'] ['17']);
        return True;

    }


    public function panel()
    {
        if ($this->check() === FALSE) {
            return FALSE;
        }
        Run::import('yonetim', '_index');
        $this->site ['title'] = $this->lang ['mod_user'] ['5'];
        $this->tema->veri('site', $this->site);
        //print_r($_COOKIE);
        $this->tema->veri('tpl', 'mod_user/panel.tpl');
    }

    public function ekle()
    {
        // Listeleme Yetkisi varmı?
        if ($this->user->yetki('mod_user', 'ekle', $this->lang ['mod_user'] ['rhata'] ['ekle'], 1) === False) {
            return False;
        }
        Run::import('yonetim', '_index');
        if (Req::is_Post('ekle')) {
            $this->_ekle();
        }
        $g = Load::model('mod_user_group');
        $group = Helper::nested($g->all()->toArray(), 'alt', 'group_id');
        $this->site ['title'] = $this->lang ['mod_user'] ['admin'] ['1'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('group', $group);
        $this->tema->veri('tpl', 'mod_user/ekle.tpl');
    }

    private function _ekle()
    {
        $this->val = Load::validator('mod_user');
        if ($this->val->ekle()) {
            $this->tema->veri('username', Req::Post_Safe('username'));
            $this->tema->veri('password', Req::Post_Safe('password'));
            $this->tema->veri('email', Req::Post_Safe('email'));
            $this->tema->veri('ad', Req::Post_Safe('ad'));
            $this->tema->veri('soyad', Req::Post_Safe('soyad'));
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $u = Load::model('mod_user');
            $u->username = Req::Post_Safe('username');
            $u->password = md5(Req::Post_Safe('password'));
            $u->email = Req::Post_Safe('email');
            $u->group_id = Req::Post_Safe('group_id');
            $u->ad = Req::Post_Safe('ad');
            $u->soyad = Req::Post_Safe('soyad');
            if ($u->save()) {
                $this->_mesaj($this->lang ['mod_user'] ['admin'] ['3']);
            } else {
                $this->tema->veri('hatalar', print_r($u, TRUE));
            }

        }
    }

    // Şifremi Değiştir
    public function sifre()
    {
        // Üyemi ?
        if ($this->check() === FALSE) {
            return FALSE;
        }

        Run::import('yonetim', '_index');

        if (Req::is_Post('duzenle')) {
            $this->_sifre();
        }

        $this->site ['title'] = $this->lang ['mod_user'] ['11'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('tpl', 'mod_user/sifredegis.tpl');
    }


    private function _sifre()
    {
        $this->val = Load::validator('mod_user.sifredegis');
        if ($this->val->ekle()) {
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $u = Load::model('mod_user');
            $u->password = md5(Req::Post_Safe('sifre'));
            $u->id = $this->ses->getir('id');
            if ($u->update('id')) {
                $this->ses->kapat('password');
                $this->ses->ac('password', md5(Req::Post_Safe('sifre')));
                $this->_mesaj($this->lang ['mod_user'] ['16']);
            } else {
                $this->tema->veri('hatalar', print_r($u, TRUE));
            }

        }
    }

    // Bilgi Güncelle
    public function bilgi()
    {
        // Üyemi ?
        if ($this->check() === FALSE) {
            return FALSE;
        }

        Run::import('yonetim', '_index');

        if (Req::is_Post('duzenle')) {
            $this->_bilgi();
        }

        $u = Load::model('mod_user');
        $u->Getid($this->ses->getir('id'))->gRo();
        $d = $u->toArray();
        $this->site ['title'] = $this->lang ['mod_user'] ['17'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        $this->tema->veri('tpl', 'mod_user/bilgi.tpl');
    }

    private function _bilgi()
    {
        $this->val = Load::validator('mod_user.bilgi');
        if ($this->val->ekle()) {
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $u = Load::model('mod_user');
            $u->ad = Req::Post_Safe('ad');
            $u->soyad = Req::Post_Safe('soyad');
            $u->dtarih = Req::Post_Safe('dtarih');
            $u->email = Req::Post_Safe('email');
            $u->cep = Req::Post_Safe('cep');
            $u->yer = Req::Post_Safe('yer');
            $u->kose_adi = Req::Post_Safe('kose_adi');
            $u->hakkinda = Req::Post_Safe('hakkinda');
            $u->id = $this->ses->getir('id');


            // Eğer Resim varsa
            if (isset($_FILES ['resim']['name']) AND $_FILES ['resim']['name'] != '') {
                $resim = Load::mod('resim');
                $resim->mod = 'mod_user';
                $resim->ad = 'resim';
                $option = array(
                    '/' =>
                        array(
                            'width' => 200,
                            'height' => NULL,
                            'option' => 'width'
                        ),
                    '/thumb' =>
                        array(
                            'width' => 100,
                            'height' => NULL,
                            'option' => 'width',
                        )
                );
                if ($resim->_resize($option)) {
                    $u->resim = $resim->url;
                } else {
                    $this->_emesaj($resim->error);
                    return FALSE;
                }
            } else {
                if (isset($u->resim)) {
                    unset($u->resim);
                }
            }
            $u->update('id');
            $this->ses->ac('adsoyad', Text::title($u->ad . " " . $u->soyad));
            $this->_mesaj($this->lang ['mod_user'] ['22']);
        }
    }

    public function resimsil()
    {
        // Üyemi ?
        if ($this->check() === FALSE) {
            return FALSE;
        }
        $id = intval(Req::Post_Safe('id'));

        $this->mod_user = Load::model('mod_user');

        if ($this->mod_user->where_id($id)->getResultOne(TRUE, TRUE)->getSize() === 0) {
            exit(':');
            return FALSE;
        }


        $d = $this->mod_user->toArray();

        if ($d['id'] !== $this->ses->getir('id')) {
            exit('::');
        }

        $this->mod_user->sync();

        if ($this->mod_user->resim != '') {
            $resim = Load::mod('resim');
            $resim->mod = 'mod_user';
            $resim->url = $this->mod_user->resim;
            $option = array(
                '/' =>
                    array(
                        'width' => 200,
                        'height' => NULL,
                        'option' => 'width',
                    ),
                '/thumb' =>
                    array(
                        'width' => 100,
                        'height' => NULL,
                        'option' => 'width',
                    )
            );
            if ($resim->_delete($option)) {
                $this->mod_user->resim = '';
                $this->mod_user->update('id');
                echo 'Oke';
            } else {
                echo $resim->error;
                return FALSE;
            }
        } else {
            echo 'Oke';
        }

    }

    public function listele()
    {

        // Listeleme Yetkisi varmı?
        if ($this->user->yetki('mod_user', 'listele', $this->lang ['mod_user'] ['rhata'] ['listele'], 1) === False) {
            return False;
        }

        Run::import('yonetim', '_index');

        $this->site ['title'] = $this->lang ['mod_user'] ['admin'] ['5'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('tpl', 'mod_user/listele.tpl');
    }


    function xml()
    {
        // Listeleme Yetkisi varmı?
        if ($this->user->yetki('mod_user', 'listele', $this->lang ['mod_user'] ['rhata'] ['listele'], 1) === False) {
            return False;
        }
        $this->grid = Load::model('mod_user.usergrid');
        return $this->grid->xml();
    }



    public function datatable_json()
    {


        $i = Load::model('mod_user.user');


        $colomns = array();
        $colomns[] = 'id';
        $colomns[] = 'username';
        $colomns[] = 'email';
        $colomns[] = 'ad';
        $colomns[] = 'soyad';
        $colomns[] = 'durum';
        $colomns[] = 'grup';
        $i->select($colomns);
        $limit = 50;
        $offset = 0;
        $order = $colomns[0];;
        $sort = 'desc';

        if (Req::is_Post('order')) {
            $o = Req::Post_Safe('order');
            if (isset($o[0]['column']) && ($o[0]['column'] > 0) && isset($colomns[$o[0]['column'] - 1])

            ) {
                $order = $colomns[$o[0]['column'] - 1];
            }
            if (isset($o[0]['dir']) && in_array($o[0]['dir'], array('asc', 'desc'))) {
                $sort = $o[0]['dir'];
            }
        }
        $i->order($order, $sort);


        $wheres = array();

        if (Req::is_Post('arama') && count(Req::Post_Safe('arama')) > 0) {
            foreach (Req::Post_Safe('arama') AS $k => $v) {
                if (in_array($k, $colomns) && $v != '') {
                    $wheres[] = $k . " LIKE '%" . Req::Clear($v) . "%'";
                }
            }
        }

        if (Req::is_Post('arama_radio') && count(Req::Post_Safe('arama_radio')) > 0) {
            foreach (Req::Post_Safe('arama_radio') AS $k => $v) {
                if (in_array($k, $colomns) && $v != '') {
                    $wheres[] = $k . "='" . Req::Clear($v) . "'";
                }
            }
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
        $data = array();


        $durum_list = array(
            'e' => array('success', $this->lang['mod_user']['grid']['27']),
            'h' => array('danger', $this->lang['mod_user']['grid']['28']),
            'a' => array('danger', $this->lang['mod_user']['grid']['29']),
        );




        if ($getCount > 0) {
            foreach ($i->toArray() as $icerik) {

                $last = "";
                if (Roll::varmi('mod_user', 'duzenle')) {
                    $last .= '<button onclick="mod_user_table_ajax.akm.processDuzenle(' . $icerik['id'] . ');return false;" class="btn default btn-xs purple">
    <i class="fa fa-edit"></i> ' . $this->lang['mod_user']['button']['duzenle'] . ' </button>';
                }
                if (Roll::varmi('mod_user', 'sil')) {
                    $last .= '<button onclick="mod_user_table_ajax.akm.processSil(\'' . $icerik['id'] . '\'); return false;" class="btn default btn-xs red">
    <i class="fa fa-trash-o"></i> ' . $this->lang['mod_user']['button']['sil'] . ' </button>';
                }
                $data[] = array(
                    '<input type="checkbox" name="id[]" value="' . $icerik['id'] . '">',
                    $icerik['id'],
                    $icerik['username'],
                    $icerik['email'],
                    $icerik['ad'],
                    $icerik['soyad'],
                    '<span class="label label-' . $durum_list[$icerik['durum']]['0'] . '">' . $durum_list[$icerik['durum']]['1'] . '</span>',
                    $icerik['grup'],
                    $last
                );

            }
        }
        $ii = new user_model();
        echo json_encode(array(
            'data' => $data,
            'recordsFiltered' => $getCount,
            'recordsTotal' => $ii->getCount(),
        ));

    }




    function sil()
    {
        // Listeleme Yetkisi varmı?
        if ($this->user->yetki('mod_user', 'sil', $this->lang ['mod_user'] ['rhata'] ['sil']) === False) {
            return False;
        }
        if (Req::is_Post('sil')) {
            return $this->_sil(Req::Post_Safe('sil'));
        }
    }

    public function _sil($sil)
    {
        $this->i = Load::model('mod_user');
        foreach ($sil as $s) {
            $this->i->getid($s)->gRo()->sync()->delete();

            if ($this->i->resim != '') {
                $resim = Load::mod('resim');
                $resim->mod = 'mod_user';
                $resim->url = $this->i->resim;
                $option = array(
                    '/' =>
                        array(
                            'width' => 200,
                            'height' => NULL,
                            'option' => 'width',
                        ),
                    '/thumb' =>
                        array(
                            'width' => 100,
                            'height' => NULL,
                            'option' => 'width',
                        )
                );
                if ($resim->_delete($option) === FALSE) {
                    echo $resim->error;

                }
            }


        }
        echo 'Oke';
    }

    public function duzenle($id = NULL)
    {

        // Listeleme Yetkisi varmı?
        if ($this->user->yetki('mod_user', 'duzenle', $this->lang ['mod_user'] ['rhata'] ['duzenle'], 1) === False) {
            return False;
        }
        Run::import('yonetim', '_index');

        $this->i = Load::model('mod_user');
        $id = Req::Url_Clear($id);

        if ($this->i->exists_id($id) === False) {
            $this->_emesaj($this->lang['mod_user']['hata']['31']);
            return;
        }

        if (Req::is_Post('duzenle')) {
            $this->_duzenle($id);
        }


        $d = $this->i->getResultOne()->toArray();
        $d ['username'] = Req::is_Post('username') ? Req::Post('username') : $d ['username'];
        $d ['email'] = Req::is_Post('email') ? Req::Post('email') : $d ['email'];

        // Üye Grubunu çekelim
        $g = Load::model('mod_user_group');
        $group = Helper::nested($g->all()->toArray(), 'alt', 'group_id');

        $this->site ['title'] = $this->lang ['mod_user'] ['admin'] ['6'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        //print_r($group);
        $this->tema->veri('group', $group);
        $this->tema->veri('tpl', 'mod_user/duzenle.tpl');

    }


    private function _duzenle($id)
    {

        $this->val = Load::validator('mod_user.duzenle');

        if ($this->val->ekle($id)) {
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $u = Load::model('mod_user');
            $u->id = $id;
            $u->username = Req::Post_Safe('username');
            $u->email = Req::Post_Safe('email');
            $u->group_id = Req::Post_Safe('group_id');
            $u->ad = Req::Post_Safe('ad');
            $u->soyad = Req::Post_Safe('soyad');
            if (Req::Post_Safe('password') != '') {
                $u->password = md5(Req::Post_Safe('password'));
            }
            if ($u->update('id')) {
                $this->_mesaj($this->lang ['mod_user'] ['admin'] ['8']);
            } else {
                $this->tema->veri('hatalar', print_r($u, TRUE));
            }

        }
    }


    public function acl()
    {

        // Listeleme Yetkisi varmı?
        if ($this->user->yetki('mod_user', 'acl', $this->lang ['mod_user'] ['rhata'] ['acl'], 1) === False) {
            return False;
        }

        Run::import('yonetim', '_index');


        if (Req::is_Post('gonder')) {

            $this->_acl();
        }

        Load::modelfile('mod_user.mod_user_perm'); // Yetkiler Modeli
        $usergroup = Load::model('mod_user_group'); // Group Modeli
        $mods = Load::model('mods'); // Mod Modeli
        $userroll = Load::model('mod_user.mod_user_roll'); // Rol Modeli

        $this->site ['title'] = $this->lang ['mod_user'] ['admin'] ['9'];

        $this->tema->veri('site', $this->site);
        $this->tema->veri('tpl', 'mod_user/acl.tpl');


        // Tüm Grupları Çekelim
        $usergroup->all();
        $gruplar = $usergroup->toArray();
        // Grupları Temaya Gönderelim
        $this->tema->veri('gruplar', $gruplar);


        // Modları Çekelim
        $mods->order('mod_id', 'DESC')->all('asc');


        $modlar = $mods->toArray();
        $this->tema->veri('modlar', $modlar);

        // Rolleri Çekelim
        $userroll->all();
        $roller = $userroll->toArray();


        // Modüllerin Dil Dosyalarını Yükleyelim
        foreach ($modlar as $mod) {
            $this->tema->lang(Load::lang($mod ['mod_name']));
            // Modülleri ve Rolleri Ayarlayalım
            foreach ($roller AS $roll) {
                if ($roll['mod_id'] === $mod['mod_id']) {
                    $sroll[$roll['mod_id']][] = $roll;
                }
            }
        }

        $permler = Load::model('mod_user.mod_user_perm');
        $permler->order('mod_id', 'DESC')->all();
        $this->tema->veri('permler', $permler->toArray());

        $this->tema->veri('roller', $sroll);
    }


    private function _acl()
    {
        Load::libfile('Database');
        $perm = Load::model('mod_user.mod_user_perm'); // Yetkiler Modeli
        $db = new Database();
        $y = Req::Post_Safe('list');

        // Trans Action Başlatalım
        $db->start_transaction();
        // Tabloyu Boşaltalım
        $db->truncate("mod_user_perm");

        foreach ($y as $d => $g) {
            foreach ($g as $f => $m) {
                foreach ($m as $k => $u) {

                    $perm->group_id = $d;
                    $perm->roll_id = $k;
                    $perm->mod_id = $f;
                    if ($perm->save() === False) {
                        // Eğer Herhangi Bir Hata oLursa Geri Alalım Herşeyi.
                        $db->rollback();
                        $this->_emesaj(print_r($db, TRUE));
                    }
                }
            }
        }
        // Eğer Sorun Çıkmaz İse Commit Edip Uygulayalım
        $db->commit();
        // yöneticinin Rollerini güncelleyelim
        $this->user->_roll($this->ses->getir('group_id'));
        $this->_mesaj($this->lang ['mod_user'] ['admin'] ['12']);
    }


    // Giriş Yapmışmı Yapmamış Bakalım
    private function _check_login()
    {
        // Giriş Yapmışmı?
        if ($this->user->checklogin() === True) {
            Helper::yonlendir($this->site['adres'] . "mod_user/panel", 2);
            return TRUE;
        }
        return FALSE;
    }

    // Üye Girişi Yapmamışsa girişe yönlendir
    public function check()
    {
        if ($this->user->checklogin() === False) {
            Helper::yonlendir($this->site ['adres'] . "mod_user/giris", 1);
            return FALSE;
        } else {
            return TRUE;
        }
    }

    // Olumlu Mesaj
    private function _mesaj($mesaj)
    {
        $this->tema->veri('oke', True);
        $this->tema->veri('mesaj', $mesaj);
    }

    // Olumsuz Mesaj
    private function _emesaj($mesaj)
    {
        $this->tema->veri('hata', True);
        $this->tema->veri('mesaj', $mesaj);
    }

    // Olumlu Mesaj
    private function _appmesaj($mesaj)
    {
        $this->apptemplate->veri('oke', True);
        $this->apptemplate->veri('mesaj', $mesaj);
    }

    // Olumsuz Mesaj
    private function _appemesaj($mesaj)
    {
        $this->apptemplate->veri('hata', True);
        $this->apptemplate->veri('mesaj', $mesaj);
    }

    // Javascript Validator İçin
    public function uyevarmi()
    {
        $val = Req::Post_Safe('validateValue');
        $id = Req::Post_Safe('validateId');
        $error = Req::Post_Safe('validateError');

        $this->b = Load::model('mod_user');
        $arrayToJs = array();
        $arrayToJs [0] = $id;
        $arrayToJs [1] = $error;
        if ($this->b->exists_username($val) === False) {
            $arrayToJs [2] = "false";
            echo '{"jsonValidateReturn":' . json_encode($arrayToJs) . '}';
        } else {
            $arrayToJs [2] = "true";
            echo '{"jsonValidateReturn":' . json_encode($arrayToJs) . '}';
        }
    }

    // Javascript Validator İçin
    public function usernamecheck()
    {
        $val = Req::Post_Safe('validateValue');
        $id = Req::Post_Safe('validateId');
        $error = Req::Post_Safe('validateError');

        $this->b = Load::model('mod_user');
        $arrayToJs = array();
        $arrayToJs [0] = $id;
        $arrayToJs [1] = $error;
        if ($this->b->exists_username($val) === False) {
            $arrayToJs [2] = "true";
            echo '{"jsonValidateReturn":' . json_encode($arrayToJs) . '}';
        } else {
            $arrayToJs [2] = "false";
            echo '{"jsonValidateReturn":' . json_encode($arrayToJs) . '}';
        }
    }

    // Javascript Validator İçin
    public function emailcheck()
    {
        $val = Req::Post_Safe('validateValue');
        $id = Req::Post_Safe('validateId');
        $error = Req::Post_Safe('validateError');

        $this->b = Load::model('mod_user');
        $arrayToJs = array();
        $arrayToJs [0] = $id;
        $arrayToJs [1] = $error;
        if ($this->b->exists_email($val) === False) {
            $arrayToJs [2] = "true";
            echo '{"jsonValidateReturn":' . json_encode($arrayToJs) . '}';
        } else {
            $arrayToJs [2] = "false";
            echo '{"jsonValidateReturn":' . json_encode($arrayToJs) . '}';
        }
    }
}