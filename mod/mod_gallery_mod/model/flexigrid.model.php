<?php
class flexigrid_model
{

    private $lang;
    public $session;
    public $json;
    public $mod_gallery_mod_model;
    public function __construct()
    {

        $this->mod_gallery_mod_model = Load::model('mod_gallery_mod');
        $this->lang = Load::lang('mod_gallery_mod');
        $this->session    = Load::lib('Session');
    }

    public function json()
    {
        $valid_sort = ["asc", "desc"];

        $colom = ['id', 'name', 'mod_name', 'mod_key', 'mod_value'];

        $rp = Req::is_Post('rp') === TRUE ? Req::Post_Safe('rp') : 10;

        $sortorder = Req::is_Post('sortorder') === TRUE ? Req::Post_Safe('sortorder') : $valid_sort['0'];

        // Keep the sort order on a valid whitelist.
        if (in_array($sortorder, $valid_sort) === False) {
            $sortorder = $valid_sort['0'];
        }

        $sortname = Req::is_Post('sortname') === TRUE ? Req::Post_Safe('sortname') : $colom['0'];

        if (in_array($sortname, $colom) === False) {
            $sortname = $colom['0'];
        }

        $unique = 'id';
        $page = Req::is_Post('page') ? Req::Post_Safe('page') : 1;
        if (
            (Role::hasPermission('mod_gallery_mod', 'moderate_edit') === TRUE)
            ||
            (Role::hasPermission('mod_gallery_mod', 'moderate_delete') === TRUE)
        ) {
            $moderate = TRUE;
        } else {
            $moderate = FALSE;
        }

        if ($moderate === FALSE) {
            $this->mod_gallery_mod_model->where_user_id($this->session->get('id'));
        }

        $like = "";
        if (Req::is_Post('query')) {
            $query = Req::Post_Safe('query');
            $qtype = Req::Post_Safe('qtype');

            $f = $moderate === FALSE ? 'where_like_' . $qtype . '_and_user_id' : 'where_like_' . $qtype . '';
            if ($moderate === FALSE) {
                $this->mod_gallery_mod_model->$f('%' . $query . '%', $this->session->get('id'));
            } else {
                $this->mod_gallery_mod_model->$f('%' . $query . '%');
            }
        }

        $this->mod_gallery_mod_model->select("id,name,mod_name,mod_key,mod_value")->gR();
        $total = $this->mod_gallery_mod_model->getSize();
        $this->json['total'] = $total;
        $this->json['page'] = $page;

        // Stop early when there are no matching rows.
        if ($total < 1) {
            echo json_encode($this->json);
            exit();
        }

        // Calculate the current page offset.
        $offset = $page != 1 ? (($page - 1) * $rp) : NULL;

        $this->mod_gallery_mod_model->limit($rp, $offset);
        $this->mod_gallery_mod_model->order($sortname, $sortorder);
        $this->mod_gallery_mod_model->getResult(TRUE, TRUE);

        // Stop early when the current page has no rows.
        if ($this->mod_gallery_mod_model->getSize() < 1) {
            echo json_encode($this->json);
            exit();
        }

        foreach ($this->mod_gallery_mod_model->toArray() as $v) {
            $array['id'] = $v[$unique];
            $array['cell'] = $this->format($v, $colom);
            $this->json['rows'][] = $array;
        }
        echo json_encode($this->json);
    }


    private function format($value, $colom)
    {
        $new = [];
        foreach ($colom as $c) {
            if (isset($value[$c])) {
                $new[] = $value[$c];
            }
        }
        return $new;

        /*if ($value['status'] === 'y') {
            $value['durum'] = $this->lang['mod_user']['grid']['active'];
        } elseif ($value['durum'] === 'n') {
            $value['durum'] = $this->lang['mod_user']['grid']['waiting_email_activation'];
        } elseif ($value['durum'] === 'a') {
            $value['durum'] = $this->lang['mod_user']['grid']['waiting_admin_activation'];
        }*/
        //return array_values($value);
    }
}
