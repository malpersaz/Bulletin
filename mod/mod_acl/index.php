<?php

/* AKM Software.
 * @2010
 * mod_acl Modül Sayfası
 * mod/mod_acl/index.php
 */

class mod_acl_mod extends indexMain
{
    public $mod_acl_model;

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
        $this->lang = Load::lang('mod_acl');
        // Dil Dosyasını Temaya Gönderelim
        $this->tema->lang($this->lang);
        // Session Kütüphanesi
        $this->session = Load::lib('Session');
        // User Model
        $this->user = Load::model('mod_user.user');
    }

    private function _ugr()
    {
        $this->db->fetch_array("SELECT name, group_id,parent_id FROM `mod_user_group` ORDER BY `order` ASC");
        $s = array_merge([['name' => $this->lang['mod_acl']['ziyaretci'], 'group_id' => '0', 'parent_id' => '0']], $this->db->toArray());
        //$s=Helper::nested($s,'parent_id','group_id');
        $this->tema->veri('_group_list', $s);
    }


    // Ekleme Sayfası
    public function add()
    {
        // Ekleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_acl', 'add', $this->lang['mod_acl']['role_errors']['add'], 1) === False) {
            return False;
        }
        if (Req::is_Post('add')) {
            $this->_add();
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_acl']['add'];
        $this->tema->tpl('mod_acl/ekle');
        $this->tema->veri('site', $this->site);
        $this->_ugr();
    }

    // Düzenleme Sayfası
    public function edit($id = NULL)
    {
        // Düzenleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_acl', 'edit', $this->lang['mod_acl']['role_errors']['edit'], 1) === False) {
            return False;
        }
        $this->mod_acl_model = Load::model('mod_acl');
        $id = Req::Url_Clear($id);
        if ($this->mod_acl_model->exists_id($id) === False) {
            $this->_error($this->lang['mod_acl']['errors']['record']);
            Run::import('management', '_index');
            $this->tema->tpl('mod_acl/edit');
            return;
        }


        $d = $this->mod_acl_model->getResultOne()->toArray();
        $d['acl'] = unserialize($d['acl']);
        // Eğer Eklenen İçerik Başkasınınsa Yönetme Permissionsi Varmı ?
        if ($this->user->checkPermission('mod_acl', 'moderate_edit', $d['user_id'], $this->lang['mod_acl']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id);
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_acl']['edit'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        $this->tema->tpl('mod_acl/edit');
        $this->_ugr();
    }

    // Listeleme Sayfası
    public function list()
    {
        // Listeleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_acl', 'list', $this->lang['mod_acl']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_acl']['list'];
        $this->tema->tpl('mod_acl/list');
        $this->tema->veri('site', $this->site);
    }

    // Ekleme Fonksiyonu
    private function _add()
    {
        $this->val = Load::validator('mod_acl.ekle');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_acl_model = Load::model('mod_acl');

            $this->mod_acl_model->name = Req::Post_Safe('name');

            $this->mod_acl_model->acl = serialize(Req::Post_Safe('group_id'));

            $this->mod_acl_model->user_id = $this->session->get('id');

            // Eğer Resim varsa
            if ($this->mod_acl_model->save(TRUE)) {
                $this->_oke_message($this->lang['mod_acl']['message']['oke']['add']);
            } else {
                // Eğer Resim varsa

                $this->_error_message($this->lang['mod_acl']['message']['oke']['add']);
            }
        }
    }

    // Düzenleme Fonksiyonu
    private function _edit($id = NULL)
    {
        $this->val = Load::validator('mod_acl.edit');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_acl_model = Load::model('mod_acl');

            $this->mod_acl_model->id = $id;
            $this->mod_acl_model->name = Req::Post_Safe('name');


            $this->mod_acl_model->acl = serialize(Req::Post_Safe('group_id'));
            if (isset($this->mod_acl_model->user_id)) {
                unset($this->mod_acl_model->user_id);
            }


            // Eğer Resim Varsa
            $this->mod_acl_model->id = $id;
            if ($this->mod_acl_model->update('id')) {
                // Cacheyi Temizleyelim
                if (Cache::exists_cache("permission-acl-" . $id)) {
                    Cache::delete_cache();
                }
                $this->_oke_message($this->lang['mod_acl']['message']['oke']['edit']);
            } else {
                // Eğer Resim varsa
                $this->_error_message($this->lang['mod_acl']['message']['error']['edit']);
            }
        }
    }

    function sil()
    {
        // Listeleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_acl',  'delete', $this->lang['mod_acl']['role_errors'][ 'delete']) === False) {
            return False;
        }
        if (Req::is_Post( 'delete')) {
            if ($this->_delete(Req::Post_Safe( 'delete'))) {
                echo 'Oke';
            } else {
                echo 'HATA';
            }
        } else {
            exit($this->lang['mod_acl']['errors']['record']);
        }
    }

    public function _delete($sil)
    {
        $this->mod_acl_model = Load::model('mod_acl');
        foreach ($sil as $s) {
            $this->mod_acl_model->flush();
            $this->mod_acl_model->where_id($s)->gRo();
            if ($this->mod_acl_model->getSize() === 0) {
                exit($this->lang['mod_acl']['errors']['record']);
            }
            $this->mod_acl_model->sync();
            // Eğer Eklenen İçerik Başkasınınsa Yönetme Permissionsi Varmı ?
            if ($this->user->checkPermission('mod_acl', 'moderate_delete', $this->mod_acl_model->user_id, $this->lang['mod_acl']['role_errors']['moderate_delete']) === False) {
                return False;
            }


            $this->mod_acl_model->delete();
        }
        return TRUE;
    }



    public function datatable_json()
    {


        $i = Load::model('mod_acl');


        $colomns = [];
        $colomns[] = 'id';
        $colomns[] = 'name';
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
            (Roll::hasPermission('mod_acl', 'moderate_edit') !== TRUE)
            and
            (Roll::hasPermission('mod_acl', 'moderate_delete') !== TRUE)
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
        $data = [];


        if ($getCount > 0) {
            foreach ($i->toArray() as $content) {

                $last = "";
                if (Roll::hasPermission('mod_acl', 'edit')) {
                    $last .= '<button onclick="mod_acl_table_ajax.akm.processEdit(' . $content['id'] . ');return false;" class="action-btn" title="' . $this->lang['mod_acl']['button']['edit'] . '" style="color: var(--md-sys-color-primary);">
    <i class="bi bi-pencil-square"></i></button>';
                }
                if (Roll::hasPermission('mod_acl',  'delete')) {
                    $last .= '<button onclick="mod_acl_table_ajax.akm.processSil(\'' . $content['id'] . '\'); return false;" class="action-btn" title="' . $this->lang['mod_acl']['button'][ 'delete'] . '" style="color: #ba1a1a;">
    <i class="bi bi-trash"></i></button>';
                }

                if (Roll::hasPermission('mod_acl', 'gallery')) {
                    $last .= '<button onclick="mod_acl_table_ajax.akm.processGallery(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs blue">
    <i class="fa fa-file-image-o"></i> ' . $this->lang['mod_acl']['button']['gallery'] . ' </button>';
                }

                if (Roll::hasPermission('mod_acl', 'file')) {
                    $last .= '<button onclick="mod_acl_table_ajax.akm.processDosya(\'' . $content['id'] . '\'); return false;" class="btn default btn-xs green">
    <i class="fa fa-file-zip-o"></i> ' . $this->lang['mod_acl']['button']['file'] . ' </button>';
                }
                $data[] = [
                    '<input type="checkbox" name="id[]" class="table-checkbox" value="' . $content['id'] . '">',
                    $content['id'],
                    $content['name'],
                    '<div style="display: flex; gap: 4px;">' . $last . '</div>'
                ];
            }
        }
        $ii = new mod_acl_model();
        echo json_encode([
            'data' => $data,
            'recordsFiltered' => $getCount,
            'recordsTotal' => $ii->getCount(),
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
