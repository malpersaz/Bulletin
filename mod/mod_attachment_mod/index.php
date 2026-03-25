<?php

/* AKM Software.
 * @2015
 * mod_attachment_mod Modül Sayfası
 * mod/mod_attachment_mod/index.php
 */

class mod_attachment_mod_mod extends indexMain
{
    public $mod_attachment_mod_model;

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
        $this->lang = Load::lang('mod_attachment_mod');
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
        if ($this->user->hasPermission('mod_attachment_mod', 'add', $this->lang['mod_attachment_mod']['role_errors']['add'], 1) === False) {
            return False;
        }
        if (Req::is_Post('add')) {
            $this->_add();
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_attachment_mod']['add'];
        $this->tema->tpl('mod_attachment_mod/add');
        $this->tema->veri('site', $this->site);
    }

    // Düzenleme Sayfası
    public function edit($id = NULL)
    {
        // Düzenleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_attachment_mod', 'edit', $this->lang['mod_attachment_mod']['role_errors']['edit'], 1) === False) {
            return False;
        }
        $this->mod_attachment_mod_model = Load::model('mod_attachment_mod');
        $id = Req::Url_Clear($id);
        if ($this->mod_attachment_mod_model->exists_id($id) === False) {
            $this->_error($this->lang['mod_attachment_mod']['errors']['record']);
            Run::import('management', '_index');
            $this->tema->tpl('mod_attachment_mod/edit');
            return;
        }

        $d = $this->mod_attachment_mod_model->getResultOne()->toArray();

        // Eğer Eklenen İçerik Başkasınınsa Yönetme Permissionsi Varmı ?
        if ($this->user->checkPermission('mod_attachment_mod', 'moderate_edit', $d['user_id'], $this->lang['mod_attachment_mod']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id);
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_attachment_mod']['edit'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        $this->tema->tpl('mod_attachment_mod/edit');
    }

    // Listeleme Sayfası
    public function list()
    {
        // Listeleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_attachment_mod', 'list', $this->lang['mod_attachment_mod']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_attachment_mod']['list'];
        $this->tema->tpl('mod_attachment_mod/list');
        $this->tema->veri('site', $this->site);
    }

    // Ekleme Fonksiyonu
    private function _add()
    {
        $this->val = Load::validator('mod_attachment_mod.add');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('errors', $this->val->hatalar);
        } else {
            $this->mod_attachment_mod_model = Load::model('mod_attachment_mod');

            $this->mod_attachment_mod_model->name = Req::Post_Safe('name');


            $this->mod_attachment_mod_model->mod_name = Req::Post_Safe('mod_name');


            $this->mod_attachment_mod_model->mod_key = Req::Post_Safe('mod_key');


            $this->mod_attachment_mod_model->mod_value = Req::Post_Safe('mod_value');


            $this->mod_attachment_mod_model->user_id = $this->session->getir('id');

            // Eğer Resim varsa
            if ($this->mod_attachment_mod_model->save(TRUE)) {
                $this->_oke_message($this->lang['mod_attachment_mod']['message']['oke']['add']);
            } else {
                // Eğer Resim varsa

                $this->_error_message($this->lang['mod_attachment_mod']['message']['oke']['add']);
            }
        }
    }

    // Düzenleme Fonksiyonu
    private function _edit($id = NULL)
    {
        $this->val = Load::validator('mod_attachment_mod.edit');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('errors', $this->val->hatalar);
        } else {
            $this->mod_attachment_mod_model = Load::model('mod_attachment_mod');

            $this->mod_attachment_mod_model->id = $id;
            $this->mod_attachment_mod_model->name = Req::Post_Safe('name');


            $this->mod_attachment_mod_model->mod_name = Req::Post_Safe('mod_name');


            $this->mod_attachment_mod_model->mod_key = Req::Post_Safe('mod_key');


            $this->mod_attachment_mod_model->mod_value = Req::Post_Safe('mod_value');


            if (isset($this->mod_attachment_mod_model->user_id)) {
                unset($this->mod_attachment_mod_model->user_id);
            }

            // Eğer Resim Varsa
            $this->mod_attachment_mod_model->id = $id;
            if ($this->mod_attachment_mod_model->update('id')) {
                $this->_oke_message($this->lang['mod_attachment_mod']['message']['oke']['edit']);
            } else {
                // Eğer Resim varsa
                $this->_error_message($this->lang['mod_attachment_mod']['message']['error']['edit']);
            }
        }
    }

    public function delete()
    {
        // Listeleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_attachment_mod',  'delete', $this->lang['mod_attachment_mod']['role_errors'][ 'delete']) === False) {
            return False;
        }
        if (Req::is_Post( 'delete')) {
            if ($this->_delete(Req::Post_Safe( 'delete'))) {
                echo 'Oke';
            } else {
                echo 'HATA';
            }
        } else {
            exit($this->lang['mod_attachment_mod']['errors']['record']);
        }
    }

    public function sil()
    {
        return $this->delete();
    }

    public function _delete($sil)
    {
        $this->mod_attachment_mod_model = Load::model('mod_attachment_mod');
        foreach ($sil as $s) {
            $this->mod_attachment_mod_model->flush();
            $this->mod_attachment_mod_model->where_id($s)->gRo();
            if ($this->mod_attachment_mod_model->getSize() === 0) {
                exit($this->lang['mod_attachment_mod']['errors']['record']);
            }
            $this->mod_attachment_mod_model->sync();
            // Eğer Eklenen İçerik Başkasınınsa Yönetme Permissionsi Varmı ?
            if ($this->user->checkPermission('mod_attachment_mod', 'moderate_delete', $this->mod_attachment_mod_model->user_id, $this->lang['mod_attachment_mod']['role_errors']['moderate_delete']) === False) {
                return False;
            }

            Run::import('mod_attachment', '_remove', [$this->mod_attachment_mod_model->mod_name]);
            $this->mod_attachment_mod_model->delete();
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


        $i = Load::model('mod_attachment_mod');


        $colomns = [];
        $colomns[] = 'id';
        $colomns[] = 'name';
        $colomns[] = 'mod_name';
        $colomns[] = 'mod_key';
        $colomns[] = 'mod_value';
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
            (Roll::hasPermission('mod_attachment_mod', 'moderate_edit') !== TRUE)
            and
            (Roll::hasPermission('mod_attachment_mod', 'moderate_delete') !== TRUE)
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


        if ($getCount > 0) {
            foreach ($i->toArray() as $content) {

                $last = "";
                if (Roll::hasPermission('mod_attachment_mod', 'edit')) {
                    $last .= '<button onclick="mod_attachment_mod_table_ajax.akm.processEdit(' . $content['id'] . ');return false;" class="btn default btn-xs purple">
    <i class="fa fa-edit"></i> ' . $this->lang['mod_attachment_mod']['button']['edit'] . ' </button>';
                }
                if (Roll::hasPermission('mod_attachment_mod',  'delete')) {
                    $last .= '<button onclick="mod_attachment_mod_table_ajax.akm.processSil(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs red">
    <i class="fa fa-trash-o"></i> ' . $this->lang['mod_attachment_mod']['button'][ 'delete'] . ' </button>';
                }

                if (Roll::hasPermission('mod_attachment_mod', 'gallery')) {
                    $last .= '<button onclick="mod_attachment_mod_table_ajax.akm.processGallery(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs blue">
    <i class="fa fa-file-image-o"></i> ' . $this->lang['mod_attachment_mod']['button']['gallery'] . ' </button>';
                }

                if (Roll::hasPermission('mod_attachment_mod', 'file')) {
                    $last .= '<button onclick="mod_attachment_mod_table_ajax.akm.processDosya(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs green">
    <i class="fa fa-file-zip-o"></i> ' . $this->lang['mod_attachment_mod']['button']['file'] . ' </button>';
                }
                $data[] = [
                    '<input type="checkbox" name="id[]" value="' . $content['id'] . '">',
                    $content['id'],
                    $content['name'],
                    $content['mod_name'],
                    $content['mod_key'],
                    $content['mod_value'],
                    $last
                ];
            }
        }
        $ii = new mod_attachment_mod_model();
        echo json_encode([
            'data' => $data,
            'recordsFiltered' => $getCount,
            'recordsTotal' => $ii->getCount(),
            // 'customActionStatus'=>'OK',
            ///'customActionMessage'=>'Oldumu Şimdi?'
        ]);
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
