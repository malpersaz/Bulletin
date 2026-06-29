<?php
class mod_announcements_helper extends indexBase
{

    public function __construct()
    {
        parent::__construct();
    }

    public function _latest_announcements($limit, $select)
    {
        $m = Load::model('mod_announcements')->select($select)->where_lang_and_status($this->site['lang'], 'y')->limit($limit)->order('created_at', 'DESC');
        $m->gR();
        if ($m->getSize() !== 0) {
            return $m->toArray();
            // $this->tema->ctx_set('latest_announcements', $m->toArray());
        }
        unset($m);
        return false;
    }

    public function announcement($id)
    {
        $id = Req::Url_Clear($id);
        if (intval($id) === 0) {
            return $this->_404();
        }

        $announcement = Load::model('mod_announcements');
        $announcement->where_id_and_status_and_lang($id, 'y', $this->site['lang']);
        $announcement->limit(1);
        $announcement->gRo();
        if ($announcement->getSize() !== 0) {
            $announcement->sync();
            // Permission Kontrol
            if ($this->_permission_kontrol($announcement->acl_id)) {
                return;
            }


            $this->tema->ctx_set('d', $announcement->toArray());
            if (isset($announcement->gallery_id) && $announcement->gallery_id != 0) {
                $this->_gallery_resimgetir('mod_announcements', $announcement->gallery_id);
            } else {
                $this->_gallery_resimgetir('mod_announcements', $announcement->id);
            }

            if (isset($announcement->file_id) && $announcement->file_id != 0) {
                $this->_mod_attachment('mod_announcements', $announcement->file_id);
            } else {
                $this->_mod_attachment('mod_announcements', $announcement->id);
            }
        } else {
            return $this->_404();
        }
        $this->tema->tpl('index/mod_announcements/announcement');
        $this->_index();
        unset($announcement);
    }

    public function announcements()
    {
        $announcement = Load::model('mod_announcements');
        $db = Load::lib('Database');
        $isSqlite = ($db->getAdapterType() && strpos($db->getAdapterType(), 'PDO_SQLITE') !== false);

        if ($isSqlite) {
            $sql = "SELECT strftime('%Y', MAX(created_at)) AS max_yil, strftime('%Y', MIN(created_at)) AS min_yil FROM `mod_announcements` WHERE 1";
        } else {
            $sql = "SELECT YEAR(MAX(created_at))  AS max_yil,YEAR(MIN(created_at))  AS min_yil FROM `mod_announcements` WHERE 1";
        }

        $announcement->fetch_array($sql);
        $announcement->getResultOne();
        $this->tema->ctx_set('arsiv_yil', $announcement->toArray());
        $d['name'] = $this->lang['index']['mod_announcements']['5'];
        $d['title'] = $this->lang['index']['mod_announcements']['5'];

        $d['keywords'] = $this->lang['index']['mod_announcements']['5'];

        $d['description'] = $this->lang['index']['mod_announcements']['5'];
        $this->tema->ctx_set('d', $d);


        if (Req::is_Post('yil') and Req::is_Post('ay')) {

            $yil = Req::Post_Safe('yil');
            $ay = Req::Post_Safe('ay');

            if ($yil === "*") {
                $yil = NULL;
            }

            if ($ay === "*") {
                $ay = NULL;
            }
            $announcement->select('id,name,created_at,summary');
            if ($yil !== NULL && $ay !== NULL) {
                if ($isSqlite) {
                    $announcement->where(
                        "strftime('%Y', created_at)='" . $yil . "' AND
                        cast(strftime('%m', created_at) as integer)=" . intval($ay) . " AND
                        lang='" . $this->site['lang'] . "' AND
                        status = 'y'"
                    );
                } else {
                    $announcement->where(
                        "YEAR(created_at)='" . $yil . "' AND
                        MONTH(created_at) ='" . $ay . "' AND
                        lang='" . $this->site['lang'] . "' AND
                        status = 'y'"
                    );
                }
                $announcement->gR();
            } elseif ($yil !== NULL) {
                if ($isSqlite) {
                    $announcement->where(
                        "strftime('%Y', created_at)='" . $yil . "' AND
                        lang='" . $this->site['lang'] . "' AND
                        status = 'y'"
                    );
                } else {
                    $announcement->where(
                        "YEAR(created_at)='" . $yil . "' AND
                        lang='" . $this->site['lang'] . "' AND
                        status = 'y'"
                    );
                }
                $announcement->gR();
            } elseif ($ay !== NULL) {
                if ($isSqlite) {
                    $announcement->where(
                        "cast(strftime('%m', created_at) as integer)=" . intval($ay) . " AND
                        lang='" . $this->site['lang'] . "' AND
                        status = 'y'"
                    );
                } else {
                    $announcement->where(
                        "MONTH(created_at) ='" . $ay . "' AND
                        lang='" . $this->site['lang'] . "' AND
                        status = 'y'"
                    );
                }
                $announcement->gR();
            } else {
                $announcement->where_status_and_lang('y', $this->site['lang']);
                $announcement->gR();
            }
            if ($announcement->getSize() !== 0) {
                $this->tema->ctx_set('announcements', $announcement->toArray());
            } else {
                $this->tema->ctx_set('bulunamadi', True);
            }

            // Send month and year to template
            if ($yil !== NULL) {
                $this->tema->ctx_set('yil', $yil);
            }
            if ($ay !== NULL) {
                $this->tema->ctx_set('ay', $ay);
            }
        }
        $this->tema->tpl('index/mod_announcements/announcements');
        $this->_index();
    }
}
