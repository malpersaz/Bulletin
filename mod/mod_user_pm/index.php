<?php

/* AKM Software
 * @2010
 * Üyelik Özel Mesaj Modülü
 * mod/mod_user_pm/index.php
 */

class mod_user_pm_mod extends indexMain
{

    public $u;
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
        $this->lang = Load::lang('mod_user_pm');
        // Dil Dosyasını Temaya Gönderelim
        $this->tema->lang($this->lang);
        // User Model
        $this->user = Load::model('mod_user.user');
        // Session Kütüphanesi
        $this->session = Load::lib('Session');
    }

    private function setTemplateErrors($errors)
    {
        $this->tema->veri('errors', $errors);
    }

    private function setTemplateFormData(array $data)
    {
        foreach ($data as $key => $value) {
            $this->tema->veri($key, $value);
        }
    }

    private function setSuccessMessage($message)
    {
        $this->tema->veri('oke', True);
        $this->tema->veri('success', True);
        $this->tema->veri('message', $message);
    }

    private function setErrorMessage($message)
    {
        $this->tema->veri('error', True);
        $this->tema->veri('message', $message);
    }

    private function redirectTo($path)
    {
        $target = $this->site['saddress'] . ltrim($path, '/');
        header('Location: ' . $target);
        exit;
    }

    // Özel Mesaj Oku
    public function inbox_read($id = NULL)
    {
        if ($id == NULL) {
            $this->redirectTo('mod_user_pm/inbox');
        }
        //$id=Req::Url_Clear($id);

        if (intval($id) === 0) {
            $this->redirectTo('mod_user_pm/inbox');
        }
        // Üyemi?
        Run::import('mod_user', 'check');

        $inbox = Load::model('mod_user_pm.inbox');
        $inbox->where_id_and_reader_id($id, $this->session->get('id'));

        if ($inbox->gRo()->getSize() === 0) {
            exit('1');
        }

        $inbox = Load::model('mod_user_pm.inbox');

        $join = [
            [
                'table' => [
                    'mod_user_pm_inbox',
                    [
                        'id AS id',
                        'status AS status',
                        'subject AS subject',
                        'message AS message',
                        'created_at AS created_at',
                        'reader_id'
                    ]
                ],
                'alias' => [
                    'mod_user',
                    [
                        'username AS username',
                        'name AS u_name',
                        'last_name AS u_last_name'
                    ]
                ],
                'condition' => [
                    'mod_user.id',
                    'mod_user_pm_inbox.user_id'
                ],
                'type' => 'LEFT'
            ]
        ];
        $inbox->table_join($join);

        $inbox->where([
            'mod_user_pm_inbox.id' => $id,
            'condition' => 'AND',
            'mod_user_pm_inbox.reader_id' => $this->session->get('id')
        ]);
        //$inbox->order('okunma', 'ASC');
        //$inbox->where_reader_id_and_id($this->session->get('id'));



        $inbox->gRo();
        $inbox->sync();
        if ($inbox->status != 'y') {
            $inbox->status = 'y';
            $this->session->set('okunmamis', $this->session->get('okunmamis') - 1);
            $inbox->update();
        }


        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_user_pm']['inbox_title'];
        $this->tema->veri('veri', $inbox->toArray());
        $this->tema->tpl('mod_user_pm/inbox_read');
        $this->tema->veri('site', $this->site);
    }

    // Özel Mesaj Oku
    public function sent_read($id = NULL)
    {
        if ($id == NULL) {
            $this->redirectTo('mod_user_pm/sent');
        }
        // Üyemi?
        Run::import('mod_user', 'check');

        $sent = Load::model('mod_user_pm.sent');
        $sent->where_id_and_user_id($id, $this->session->get('id'));

        if ($sent->gRo()->getSize() === 0) {
            exit('1');
        }

        $sent = Load::model('mod_user_pm.sent');

        $join = [
            [
                'table' => [
                    'mod_user_pm_sent',
                    [
                        'id AS id',
                        'subject AS subject',
                        'message AS message',
                        'created_at AS created_at',
                        'reader_id'
                    ]
                ],
                'alias' => [
                    'mod_user',
                    [
                        'username AS username',
                        'name AS u_name',
                        'last_name AS u_last_name'
                    ]
                ],
                'condition' => [
                    'mod_user.id',
                    'mod_user_pm_sent.reader_id'
                ],
                'type' => 'LEFT'
            ]
        ];
        $sent->table_join($join);
        //$inbox->order('okunma', 'ASC');
        //$inbox->where_reader_id_and_id($this->session->get('id'));
        $sent->where([
            'mod_user_pm_sent.id' => $id,
            'condition' => 'AND',
            'mod_user_pm_sent.user_id' => $this->session->get('id')
        ]);
        $sent->gRo();



        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_user_pm']['sent_title'];
        $this->tema->veri('veri', $sent->toArray());
        $this->tema->tpl('mod_user_pm/sent_read');
        $this->tema->veri('site', $this->site);
    }

    public function check_pm()
    {

        Run::import('mod_user', 'check');

        $inbox = Load::model('mod_user_pm.inbox');

        $join = [
            [
                'table' => [
                    'mod_user_pm_inbox',
                    [
                        'id AS id',
                        'status AS status',
                        'subject',
                        'created_at AS created_at',
                        'reader_id'
                    ]
                ],
                'alias' => [
                    'mod_user',
                    [
                        'username AS username',
                        'name AS u_name',
                        'last_name AS u_last_name'
                    ]
                ],
                'condition' => [
                    'mod_user.id',
                    'mod_user_pm_inbox.user_id'
                ],
                'type' => 'LEFT'
            ]
        ];
        $inbox->table_join($join);
        $inbox->where("mod_user_pm_inbox.created_at > '" . date("Y-m-d H:i:s", $this->session->get('check_pm')) . "' AND reader_id='" . $this->session->get('id') . "'");
        //$inbox->where_reader_id($this->session->get('id'));
        $inbox->order('created_at', 'DESC');

        $size = $inbox->getCount();

        $this->session->set('check_pm', time());

        if ($size != 0) {
            $this->session->set("okunmamis", $this->session->get('okunmamis') + $size);
            $j = [
                'okunmamis' => $size
            ];
            echo json_encode($j);
            return;
        }

        echo json_encode([
            'okunmamis' => FALSE
        ]);
        //print_r($inbox);
    }

    // Özel Mesajlar
    public function inbox()
    {

        Run::import('mod_user', 'check');

        Run::import('management', '_index');


        $inbox = Load::model('mod_user_pm.inbox');

        $join = [
            [
                'table' => [
                    'mod_user_pm_inbox',
                    [
                        'id AS id',
                        'status AS status',
                        'subject',
                        'created_at AS created_at',
                        'reader_id'
                    ]
                ],
                'alias' => [
                    'mod_user',
                    [
                        'username AS username',
                        'name AS u_name',
                        'last_name AS u_last_name'
                    ]
                ],
                'condition' => [
                    'mod_user.id',
                    'mod_user_pm_inbox.user_id'
                ],
                'type' => 'LEFT'
            ]
        ];
        $inbox->table_join($join);
        $inbox->where_reader_id($this->session->get('id'));
        $inbox->order('created_at', 'DESC');
        $inbox->all();
        //print_r($inbox);
        $this->site['title'] = $this->lang['mod_user_pm']['inbox_title'];

        if ($inbox->getSize() !== 0) {
            $this->tema->veri('messages', $inbox->toArray());
        }
        $this->tema->tpl('mod_user_pm/inbox');
        $this->tema->veri('site', $this->site);
    }

    // Özel Mesajlar
    public function sent()
    {

        Run::import('mod_user', 'check');

        Run::import('management', '_index');


        $sent = Load::model('mod_user_pm.sent');

        $join = [
            [
                'table' => [
                    'mod_user_pm_sent',
                    [
                        'id AS id',
                        'subject AS subject',
                        'created_at AS created_at',
                        'reader_id'
                    ]
                ],
                'alias' => [
                    'mod_user',
                    [
                        'username AS username',
                        'name AS u_name',
                        'last_name AS u_last_name'
                    ]
                ],
                'condition' => [
                    'mod_user.id',
                    'mod_user_pm_sent.reader_id'
                ],
                'type' => 'LEFT'
            ]
        ];
        $sent->table_join($join);
        $sent->where_user_id($this->session->get('id'));
        $sent->order('created_at', 'DESC');
        $sent->all();
        $this->site['title'] = $this->lang['mod_user_pm']['sent_title'];

        if ($sent->getSize() !== 0) {
            $this->tema->veri('messages', $sent->toArray());
        }
        $this->tema->tpl('mod_user_pm/sent');
        $this->tema->veri('site', $this->site);
    }

    // Öm Gönderme Fonksiyonu
    public function send($nick = NULL, $subject = NULL)
    {
        // Üyemi?
        Run::import('mod_user', 'check');

        // Gönderme Permissionsi varmı?
        if ($this->user->hasPermission('mod_user_pm', 'send', $this->lang['mod_user_pm']['role_errors']['send'], 1) === False) {
            return False;
        }

        if ($nick != NULL) {
            $this->setTemplateFormData([
                'recipient' => Req::Url_Clear($nick)
            ]);
        }
        if ($subject != NULL) {
            $this->setTemplateFormData([
                'subject' => $this->lang['mod_user_pm']['reply_prefix'] . Req::Url_Clear($subject),
            ]);
        }


        if (Req::is_Post('send')) {
            $this->_send();
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_user_pm']['send_private_message_title'];
        $this->tema->tpl('mod_user_pm/send');
        $this->tema->veri('site', $this->site);
    }

    private function _send()
    {
        $this->val = Load::validator('mod_user_pm.send');
        if ($this->val->check()) {
            $this->setTemplateFormData([
                'recipient' => Req::Post_Safe('recipient'),
                'subject' => Req::Post_Safe('subject'),
                'message' => Req::Post_Safe('message'),
            ]);
            $this->setTemplateErrors($this->val->errors);
        } else {
            $this->u = Load::model('mod_user');
            $this->u->Getusername(Req::Post_Safe('recipient'))->gRo()->sync();

            $message = Req::Post_Safe('message');
            $subject = Req::Post_Safe('subject');
            $this->model = Load::model('mod_user_pm.sent');
            if ($this->model->send_user_message(
                $subject,
                $message,
                $this->u->id,
                $this->session->get('id')
            )) {
                $this->setSuccessMessage($this->lang['mod_user_pm']['message_sent_success']);
            } else {
                $this->setErrorMessage(print_r($this->model, TRUE));
            }
        }
    }

    // Toplu ÖM gönderme Fonksiyonu
    public function bulk()
    {
        // Üyemi?
        Run::import('mod_user', 'check');

        // Gönderme Permissionsi varmı?
        if ($this->user->hasPermission('mod_user_pm', 'bulk', $this->lang['mod_user_pm']['role_errors']['bulk'], 1) === False) {
            return False;
        }

        if (Req::is_Post('bulk')) {
            $this->_bulk();
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_user_pm']['bulk_pm_area_desc'];
        $this->tema->tpl('mod_user_pm/bulk');
        $this->tema->veri('site', $this->site);
    }

    private function _bulk()
    {
        $this->val = Load::validator('mod_user_pm.bulk');
        if ($this->val->check()) {
            $this->setTemplateFormData([
                'subject' => Req::Post_Safe('subject'),
                'message' => Req::Post_Safe('message'),
                'recipients' => implode(',', Req::Post_Safe('recipients')),
            ]);
            $this->setTemplateErrors($this->val->errors);
        } else {
            $data = array_unique(array_values(array_filter(Req::Post_Safe('recipients'))));
            $subject = Req::Post_Safe('subject');
            $message = Req::Post_Safe('message');
            $this->model = Load::model('mod_user_pm.sent');
            if ($this->model->send_bulk_message($subject, $message, $data, $this->session->get('id'))) {
                $this->setSuccessMessage($this->lang['mod_user_pm']['bulk_sent_success']);
            } else {
                $this->setErrorMessage(print_r($this->model, TRUE));
            }
        }
    }





    // Guruba Toplu ÖM gönderme Fonksiyonu
    public function group_bulk()
    {
        // Üyemi?
        Run::import('mod_user', 'check');

        // Gönderme Permissionsi varmı?
        if ($this->user->hasPermission('mod_user_pm', 'group_bulk', $this->lang['mod_user_pm']['role_errors']['group_bulk'], 1) === False) {
            return False;
        }

        if (Req::is_Post('group_bulk')) {
            $this->_group_bulk();
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_user_pm']['bulk_group_message_title'];

        $grup = Load::model('mod_user_group');
        $grup->order('order', 'ASC')->all();
        $gr = Helper::nested($grup->toArray(), 'parent_id', 'group_id');
        $this->tema->veri('user_groupm', $gr);

        $this->tema->tpl('mod_user_pm/group_bulk');
        $this->tema->veri('site', $this->site);
    }

    private function _group_bulk()
    {
        $this->val = Load::validator('mod_user_pm.group_bulk');
        if ($this->val->check()) {
            $this->setTemplateFormData([
                'subject' => Req::Post_Safe('subject'),
                'message' => Req::Post_Safe('message'),
            ]);
            $this->setTemplateErrors($this->val->errors);
        } else {
            $subject = Req::Post_Safe('subject');
            $message = Req::Post_Safe('message');
            $groupid = Req::Post_Safe('group_id');
            $this->model = Load::model('mod_user_pm.sent');
            if ($this->model->send_user_group_message($subject, $message, $groupid, $this->session->get('id'))) {
                $this->setSuccessMessage($this->lang['mod_user_pm']['group_sent_success']);
            } else {
                $this->setErrorMessage($this->lang['mod_user_pm']['error']['group_not_found']);
            }
        }
    }

    public function inbox_delete()
    {
        $this->model = Load::model('mod_user_pm.inbox');
        $this->model->where_id_and_reader_id(Req::Post_Safe('id'), $this->session->get('id'));
        $this->model->gRo();
        if ($this->model->getSize() === 0) {
            echo $this->lang['mod_user_pm']['no_messages_yet'];
            exit();
        }
        $this->model->delete();
        echo "Oke";
    }

    public function datatable_json()
    {
        Run::import('mod_user', 'check');
        $inbox = Load::model('mod_user_pm.inbox');

        // Base Join
        $join = [
            [
                'table' => [
                    'mod_user_pm_inbox',
                    ['id', 'status', 'subject', 'message', 'created_at', 'reader_id']
                ],
                'alias' => [
                    'mod_user',
                    ['username', 'image']
                ],
                'condition' => ['mod_user.id', 'mod_user_pm_inbox.user_id'],
                'type' => 'LEFT'
            ]
        ];
        $inbox->table_join($join);

        // Filters
        $reader_id = $this->session->get('id');
        $where = "mod_user_pm_inbox.reader_id = '$reader_id'";

        // Search
        if (Req::is_Post('arama')) {
            $arama = Req::Post_Safe('arama');
            if (isset($arama['subject']) && $arama['subject'] != '') {
                $search = Req::Clear($arama['subject']);
                $where .= " AND (mod_user_pm_inbox.subject LIKE '%$search%' OR mod_user.username LIKE '%$search%')";
            }
        }

        $inbox->where($where);
        
        // Pagination
        $start = intval(Req::Post_Safe('start'));
        $length = intval(Req::Post_Safe('length'));
        $length = $length > 0 ? $length : 10;

        $total_count = $inbox->getCount();
        
        $inbox->order('mod_user_pm_inbox.created_at', 'DESC');
        $inbox->limit($length, $start);
        $inbox->gR();

        $data = [];
        if ($total_count > 0) {
            foreach ($inbox->toArray() as $row) {
                // Format for JS renderMessages
                // [checkbox, id, subject, statusHtml, created_at, username, image, message]
                $data[] = [
                    '', // checkbox placeholder
                    $row['id'],
                    $row['subject'],
                    $row['status'] == 'y' ? '<span class="badge bg-light text-dark">Xwende</span>' : '<span class="badge bg-danger">Nexwende</span>',
                    $row['created_at'],
                    $row['username'],
                    $row['image'],
                    $row['message']
                ];
            }
        }

        echo json_encode([
            'draw' => intval(Req::Post_Safe('draw')),
            'recordsTotal' => $total_count,
            'recordsFiltered' => $total_count,
            'data' => $data
        ]);
    }

    public function sent_delete()
    {
        $this->model = Load::model('mod_user_pm.sent');
        $this->model->where_id_and_user_id(Req::Post_Safe('id'), $this->session->get('id'));
        $this->model->gRo();
        if ($this->model->getSize() === 0) {
            echo $this->lang['mod_user_pm']['no_messages_yet'];
            exit();
        }
        $this->model->delete();
        echo "Oke";
    }

    // Bütün Üyeler
    public function alluser()
    {
        if (
            Roll::hasPermission('mod_user_pm', 'send') === FALSE
            && Roll::hasPermission('mod_user_pm', 'bulk') === FALSE
            && Roll::hasPermission('mod_user_pm', 'group_bulk') === FALSE
            && Roll::hasPermission('mod_user_pm', 'all') === FALSE
        ) {
            exit();
        }
        $this->u = Load::model('mod_user');
        $this->u->select('username')->all();
        foreach ($this->u->toArray() as $u) {
            $dizi[] = $u['username'];
        }
        $ext = implode('","', $dizi);
        echo '["' . $ext . '"]';
    }

    public function allin()
    {
        if (
            Roll::hasPermission('mod_user_pm', 'send') === FALSE
            && Roll::hasPermission('mod_user_pm', 'bulk') === FALSE
            && Roll::hasPermission('mod_user_pm', 'group_bulk') === FALSE
            && Roll::hasPermission('mod_user_pm', 'all') === FALSE
        ) {
            exit();
        }

        if (Req::is_Post('q')) {
            $q = Req::Post_Safe('q');
            $q = '%' . $q . '%';
            $u = Load::model('mod_user');
            $u->select('username AS name,id');
            $u->where_like_username($q);
            $u->gR();
            if ($u->getSize() !== 0) {
                echo json_encode($u->toArray());
                //echo str_replace('"username"', '"name"', $a);
            } else {
                echo '[]';
            }
        } else {
            echo '[]';
        }
    }

    // Bütün Üyelere TOplu MEsaj Gönderme
    public function all()
    {
        // Gönderme Permissionsi varmı?
        if ($this->user->hasPermission('mod_user_pm', 'all', $this->lang['mod_user_pm']['role_errors']['all'], 1) === False) {
            return False;
        }

        if (Req::is_Post('all')) {
            $this->_all();
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_user_pm']['bulk_all_message_title'];
        $this->tema->tpl('mod_user_pm/all');
        $this->tema->veri('site', $this->site);
    }

    private function _all()
    {
        $this->val = Load::validator('mod_user_pm.all');
        $subjectField = 'subject';
        $messageField = 'message';
        if ($this->val->check()) {
            $this->setTemplateFormData([
                'subject' => Req::Post_Safe($subjectField),
                'message' => Req::Post_Safe($messageField),
            ]);
            $this->setTemplateErrors($this->val->errors);
        } else {
            $subject = Req::Post_Safe($subjectField);
            $message = Req::Post_Safe($messageField);
            $this->model = Load::model('mod_user_pm.sent');

            if ($this->model->send_all_user_message($subject, $message, $this->session->get('id'))) {
                $this->setSuccessMessage($this->lang['mod_user_pm']['all_sent_success']);
            } else {
                $this->setErrorMessage(print_r($this->model));
            }
        }
    }
}
