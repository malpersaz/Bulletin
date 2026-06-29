<?php
class flexigrid_model
{

    public $mod_video_model;
    public function __construct()
    {

        $this->mod_video_model = Load::model('mod_video');
        $this->lang = Load::lang('mod_video');
    }

    public function json()
    {
        $valid_sort = ["asc", "desc"];

        $colom = ['id', 'name', 'duration', 'lang', 'created_at'];

        $rp = Req::is_Post('rp') ? Req::Post_Safe('rp') : 10;
        // ORDER BY ID ASC Gibi
        if (in_array(Req::Post_Safe('sortorder'), $valid_sort) == False) {
            $sortorder = $valid_sort['0'];
        } else {
            $sortorder = Req::Post_Safe('sortorder');
        }

        $sortname = in_array(Req::Post_Safe('sortname'), $colom) == False ? $colom['0'] : Req::Post_Safe('sortname');


        $unique = 'id';
        $page = Req::is_Post('page') ? Req::Post_Safe('page') : 1;
        if (
            (Roll::hasPermission('mod_video', 'moderate_edit') === TRUE)
            ||
            (Roll::hasPermission('mod_video', 'moderate_delete') === TRUE)
        ) {
            $moderate = TRUE;
        } else {
            $moderate = FALSE;
        }

        if ($moderate === FALSE) {
            $this->mod_video_model->where_user_id($this->session->get('user_id'));
        }

        $like = "";
        if (Req::is_Post('query')) {
            $query = Req::Post_Safe('query');
            $qtype = Req::Post_Safe('qtype');

            $f = $moderate === FALSE ? 'where_like_' . $qtype . '_and_user_id' : 'where_like_' . $qtype . '';
            if ($moderate === FALSE) {
                $this->mod_video_model->$f('%' . $query . '%', $this->session->get('user_id'));
            } else {
                $this->mod_video_model->$f('%' . $query . '%');
            }
        }

        $this->mod_video_model->select("id,name,duration,lang,created_at")->gR();
        $toplam = $this->mod_video_model->getSize();
        $this->json['total'] = $toplam;
        $this->json['page'] = $page;

        // Eğer Sonuç Yoksa Malesef
        if ($toplam < 1) {
            echo json_encode($this->json);
            exit();
        }

        // Ofset Değeri
        $offset = $page != 1 ? (($page - 1) * $rp) : NULL;

        $this->mod_video_model->limit($rp, $offset);
        $this->mod_video_model->order($sortname, $sortorder);
        $this->mod_video_model->getResult(TRUE, TRUE);

        // Eğer Sonuç Yoksa
        if ($this->mod_video_model->getSize() < 1) {
            echo json_encode($this->json);
            exit();
        }

        foreach ($this->mod_video_model->toArray() as $v) {
            $array['id'] = $v[$unique];
            $array['cell'] = $this->format($v);
            $this->json['rows'][] = $array;
        }
        echo json_encode($this->json);
    }


    private function format($value)
    {
        /*if ($value['durum'] === 'y') {
            $value['durum'] = $this->lang['mod_user']['grid']['active'];
        } elseif ($value['durum'] === 'n') {
            $value['durum'] = $this->lang['mod_user']['grid']['waiting_email_activation'];
        } elseif ($value['durum'] === 'a') {
            $value['durum'] = $this->lang['mod_user']['grid']['waiting_admin_activation'];
        }*/
        return array_values($value);
    }
}
