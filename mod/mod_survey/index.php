<?php

/* AKM Software.
 * @2015
 * mod_survey Modül Sayfası
 * mod/mod_survey/index.php
 */

class mod_survey_mod extends indexMain
{
    public $grid;
    public $mod_survey_model;

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
        $this->lang = Load::lang('mod_survey');
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
        // Ekleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_survey', 'add', $this->lang['mod_survey']['role_errors']['add'], 1) === False) {
            return False;
        }
        if (Req::is_Post('add')) {
            $this->_add();
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_survey']['add'];
        $this->tema->tpl('mod_survey/ekle');
        $this->tema->veri('site', $this->site);
        $this->db->fetch_array("SELECT name,seo FROM `lang` ORDER BY seo DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $this->db->fetch_array("SELECT name,id FROM `mod_acl` ORDER BY id DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());
    }

    // Düzenleme Sayfası
    public function edit($id = NULL)
    {
        // Düzenleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_survey', 'edit', $this->lang['mod_survey']['role_errors']['edit'], 1) === False) {
            return False;
        }
        $this->mod_survey_model = Load::model('mod_survey');
        $id = Req::Url_Clear($id);
        if ($this->mod_survey_model->exists_id($id) === False) {
            $this->_error($this->lang['mod_survey']['errors']['record']);
            return;
        }

        $d = $this->mod_survey_model->getResultOne()->toArray();

        // Eğer Eklenen İçerik Başkasınınsa Yönetme Permissionsi Varmı ?
        if ($this->user->checkPermission('mod_survey', 'moderate_edit', $d['user_id'], $this->lang['mod_survey']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id);
        }

        Run::import('management', '_index');

        $sec = Load::model('mod_survey.secenek');
        $sec->where_anket_id($d['id'])->gR();

        if ($sec->getSize() !== 0) {
            $this->tema->veri('s', $sec->toArray());
            $this->tema->veri('toplamsec', $sec->getSize());
        } else {
            $this->tema->veri('toplamsec', 0);
        }


        $this->site['title'] = $this->lang['mod_survey']['edit'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        $this->tema->tpl('mod_survey/duzenle');
        $this->db->fetch_array("SELECT name,seo FROM `lang` ORDER BY seo DESC");
        $this->tema->veri('lang_lang', $this->db->getResult()->toArray());
        $this->db->fetch_array("SELECT name,id FROM `mod_acl` ORDER BY id DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());
    }

    // Listeleme Sayfası
    public function list()
    {
        // Listeleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_survey', 'list', $this->lang['mod_survey']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_survey']['list'];
        $this->tema->tpl('mod_survey/list');
        $this->tema->veri('site', $this->site);
    }

    // Xml Sayfası
    public function xml()
    {
        // Listeleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_survey', 'list', $this->lang['mod_survey']['role_errors']['list'], 1) === False) {
            return False;
        }
        $this->grid = Load::model('mod_survey.grid');
        return $this->grid->xml();
    }


    // Ekleme Fonksiyonu
    private function _add()
    {
        $this->val = Load::validator('mod_survey.ekle');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_survey_model = Load::model('mod_survey');

            $this->mod_survey_model->lang = Req::Post_Safe('lang');
            $this->mod_survey_model->soru = Req::Post_Safe('soru');
            $this->mod_survey_model->status = 'n';

            $this->mod_survey_model->acl_id = Req::Post_Safe('acl_id');
            $this->mod_survey_model->user_id = $this->session->getir('id');

            // Eğer Resim varsa
            if ($this->mod_survey_model->save(TRUE)) {

                // Seçenekleri Ekleyelim
                $secenekler = Req::Post_Safe('secenek');
                $this->secenek = Load::model('mod_survey.secenek');
                foreach ($secenekler as $s) {
                    $this->secenek->anket_id = $this->mod_survey_model->id;
                    $this->secenek->secenek = $s;
                    $this->secenek->hit = 0;
                    $this->secenek->save();
                }


                $this->_oke_message($this->lang['mod_survey']['message']['oke']['add']);
            } else {
                // Eğer Resim varsa

                $this->_error_message($this->lang['mod_survey']['message']['oke']['add']);
            }
        }
    }

    // Düzenleme Fonksiyonu
    private function _edit($id = NULL)
    {
        $this->val = Load::validator('mod_survey.duzenle');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_survey_model = Load::model('mod_survey');


            $this->mod_survey_model->lang = Req::Post_Safe('lang');
            $this->mod_survey_model->soru = Req::Post_Safe('soru');
            $this->mod_survey_model->acl_id = Req::Post_Safe('acl_id');
            if (isset($this->mod_survey_model->user_id)) {
                unset($this->mod_survey_model->user_id);
            }

            // Eğer Resim Varsa
            $this->mod_survey_model->id = $id;
            if ($this->mod_survey_model->update('id')) {


                $this->sec = Load::model('mod_survey.secenek');

                $secenekler = Req::Post_Safe('secenek');

                foreach ($secenekler as $sec => $val) {

                    if ($val !== '') {


                        if ($this->sec->exists_anket_id_and_id($id, $sec) === TRUE) {
                            $this->sec->secenek = $val;
                            $this->sec->id = $sec;
                            $this->sec->update('id');
                            $valid[] = $sec;
                        } else {
                            $this->sec->anket_id = $id;
                            $this->sec->secenek = $val;
                            $this->sec->hit = 0;
                            $this->sec->save(TRUE);
                            $valid[] = $this->sec->id;
                        }
                    }
                    //echo "Key:$sec.Value:$val<br>";

                }

                $this->sec->where_anket_id_and_not_id($id, $valid)->delete();


                $this->_oke_message($this->lang['mod_survey']['message']['oke']['edit']);
            } else {
                // Eğer Resim varsa
                $this->_error_message($this->lang['mod_survey']['message']['error']['edit']);
            }
        }
    }



    function sil()
    {
        // Listeleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_survey',  'delete', $this->lang['mod_survey']['role_errors'][ 'delete']) === False) {
            return False;
        }
        if (Req::is_Post( 'delete')) {
            if ($this->_delete(Req::Post_Safe( 'delete'))) {
                echo 'Oke';
            } else {
                echo 'HATA';
            }
        } else {
            exit($this->lang['mod_survey']['errors']['record']);
        }
    }

    public function _delete($sil)
    {
        $this->mod_survey_model = Load::model('mod_survey');
        foreach ($sil as $s) {
            $this->mod_survey_model->flush();
            $this->mod_survey_model->where_id($s)->gRo();
            if ($this->mod_survey_model->getSize() === 0) {
                exit($this->lang['mod_survey']['errors']['record']);
            }
            $this->mod_survey_model->sync();
            // Eğer Eklenen İçerik Başkasınınsa Yönetme Permissionsi Varmı ?
            if ($this->user->checkPermission('mod_survey', 'moderate_delete', $this->mod_survey_model->user_id, $this->lang['mod_survey']['role_errors']['moderate_delete']) === False) {
                return False;
            }

            // Modül varmı ve Gallery Ekleniyormu
            $this->mod = Load::model('mod_gallery_mod');
            if ($this->mod->exists_mod_name("mod_survey") === TRUE) {
                // İçeriğe Ait Gallery Varsa Silelim
                Run::import('mod_gallery', '_remove', ['mod_survey', $this->mod_survey_model->id]);
            }

            $this->mod_survey_model->delete();
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


        $i = Load::model('mod_survey');


        $colomns = [];
        $colomns[] = 'id';
        $colomns[] = 'soru';
        $colomns[] = 'status';
        $colomns[] = 'lang';
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
            (Roll::hasPermission('mod_survey', 'moderate_edit') !== TRUE)
            and
            (Roll::hasPermission('mod_survey', 'moderate_delete') !== TRUE)
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


            'y' => ['success', $this->lang['mod_survey']['admin']['radio']['status_e']],


            'n' => ['danger', $this->lang['mod_survey']['admin']['radio']['status_h']],


        ];


        if ($getCount > 0) {
            foreach ($i->toArray() as $content) {

                $last = "";
                if (Roll::hasPermission('mod_survey', 'edit')) {
                    $last .= '<button onclick="mod_survey_table_ajax.akm.processEdit(' . $content['id'] . ');return false;" class="btn default btn-xs purple">
    <i class="fa fa-edit"></i> ' . $this->lang['mod_survey']['button']['edit'] . ' </button>';
                }
                if (Roll::hasPermission('mod_survey',  'delete')) {
                    $last .= '<button onclick="mod_survey_table_ajax.akm.processSil(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs red">
    <i class="fa fa-trash-o"></i> ' . $this->lang['mod_survey']['button'][ 'delete'] . ' </button>';
                }

                if (Roll::hasPermission('mod_survey', 'gallery')) {
                    $last .= '<button onclick="mod_survey_table_ajax.akm.processGallery(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs blue">
    <i class="fa fa-file-image-o"></i> ' . $this->lang['mod_survey']['button']['gallery'] . ' </button>';
                }

                if (Roll::hasPermission('mod_survey', 'file')) {
                    $last .= '<button onclick="mod_survey_table_ajax.akm.processDosya(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs green">
    <i class="fa fa-file-zip-o"></i> ' . $this->lang['mod_survey']['button']['file'] . ' </button>';
                }
                $data[] = [
                    '<input type="checkbox" name="id[]" value="' . $content['id'] . '">',
                    $content['id'],
                    $content['soru'],
                    '<span class="label label-' . $status_list[$content['status']]['0'] . '">' . $status_list[$content['status']]['1'] . '</span>',
                    $content['lang'],
                    $last
                ];
            }
        }
        $ii = new mod_survey_model();
        echo json_encode([
            'data' => $data,
            'recordsFiltered' => $getCount,
            'recordsTotal' => $ii->getCount(),
            // 'customActionStatus'=>'OK',
            ///'customActionMessage'=>'Oldumu Şimdi?'
        ]);
    }


    // varsayilanyap Sayfası
    public function varsayilanyap()
    {
        // moderate_edit Permissionsi varmı?
        if ($this->user->hasPermission('mod_survey', 'moderate_edit', $this->lang['mod_survey']['role_errors']['moderate_edit']) === False) {
            return False;
        }
        $query = Req::Post_Safe('query');
        if ($this->_varsayilanyap($query) === TRUE) {
            echo 'Oke';
        } else {
            echo 'ERROR';
        }
    }

    private function _varsayilanyap($query = NULL)
    {
        // mod_survey Modeli Yükleyelim
        $this->mod_survey_model = Load::model('mod_survey');
        if (is_array($query)) {
            foreach ($query as $s) {
                $this->mod_survey_model->where_id($s)->gRo();
                if ($this->mod_survey_model->getSize() === 0) {
                    return FALSE;
                }
                $this->mod_survey_model->sync();
                $up = new mod_survey_model();
                $up->status = 'n';
                $up->where_lang_and_not_id($this->mod_survey_model->lang, $s)->update();
                $this->mod_survey_model->id = $s;
                $this->mod_survey_model->status = 'y';
                if ($this->mod_survey_model->update('id')) {
                    continue;
                }
                return FALSE;
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
        $this->tema->veri('hata', True);
        $this->tema->veri('hatalar', [$mesaj]);
    }

    // Error Mesaj
    private function _error($mesaj)
    {
        $this->tema->veri('error', TRUE);
        $this->tema->veri('mesaj', $mesaj);
    }
}
