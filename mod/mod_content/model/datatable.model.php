<?php
class datatable_model
{

    public $mod_content_model;
    private $lang;
    public $session;
    public $json;
    public function __construct()
    {

        $this->mod_content_model = Load::model('mod_content');
        $this->lang = Load::lang('mod_content');
        $this->session    = Load::lib('Session');
    }

    public function json()
    {
        $valid_sort = ["asc", "desc"];

        $colom = ['id', 'name', 'image', 'lang', 'status', 'created_at'];

        $rp = Req::is_Post('rp') === TRUE ? Req::Post_Safe('rp') : 10;

        $sortorder = Req::is_Post('sortorder') === TRUE ? Req::Post_Safe('sortorder') : $valid_sort['0'];

        // ORDER BY ID ASC Gibi
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
            (Roll::hasPermission('mod_content', 'moderate_edit') === TRUE)
            ||
            (Roll::hasPermission('mod_content', 'moderate_delete') === TRUE)
        ) {
            $moderate = TRUE;
        } else {
            $moderate = FALSE;
        }

        if ($moderate === FALSE) {
            $this->mod_content_model->where_user_id($this->session->getir('id'));
        }

        $like = "";
        if (Req::is_Post('query')) {
            $query = Req::Post_Safe('query');
            $qtype = Req::Post_Safe('qtype');

            $f = $moderate === FALSE ? 'where_like_' . $qtype . '_and_user_id' : 'where_like_' . $qtype . '';
            if ($moderate === FALSE) {
                $this->mod_content_model->$f('%' . $query . '%', $this->session->getir('id'));
            } else {
                $this->mod_content_model->$f('%' . $query . '%');
            }
        }

        $this->mod_content_model->select("id,name,image,lang,status,created_at")->gR();
        $toplam = $this->mod_content_model->getSize();
        $this->json['total'] = $toplam;
        $this->json['page'] = $page;

        // Eğer Sonuç Yoksa Malesef
        if ($toplam < 1) {
            echo json_encode($this->json);
            exit();
        }

        // Ofset Değeri
        $offset = $page != 1 ? (($page - 1) * $rp) : NULL;

        $this->mod_content_model->limit($rp, $offset);
        $this->mod_content_model->order($sortname, $sortorder);
        $this->mod_content_model->getResult(TRUE, TRUE);

        // Eğer Sonuç Yoksa
        if ($this->mod_content_model->getSize() < 1) {
            echo json_encode($this->json);
            exit();
        }

        foreach ($this->mod_content_model->toArray() as $v) {
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

        /*if ($value['durum'] === 'y') {
            $value['durum'] = $this->lang['mod_user']['grid']['active'];
        } elseif ($value['durum'] === 'n') {
            $value['durum'] = $this->lang['mod_user']['grid']['waiting_email_activation'];
        } elseif ($value['durum'] === 'a') {
            $value['durum'] = $this->lang['mod_user']['grid']['waiting_admin_activation'];
        }*/
        //return array_values($value);
    }
}
