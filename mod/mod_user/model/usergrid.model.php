<?php
class usergrid_model
{

    private $db;
    public $user;
    private $lang;
    public $json;
    function __construct()
    {
        $this->db = Load::lib('Database');
        $this->user = Load::model('mod_user');
        $this->lang = Load::lang('mod_user');
    }

    function xml()
    {
        $valid_sort = ["asc", "desc"];

        $colom = ['id', 'username', 'email', 'name', 'status'];

        $rp = Req::is_Post('rp') ? Req::Post_Safe('rp') : 10;
        // ORDER BY ID ASC Gibi
        if (in_array(Req::Post_Safe('sortorder'), $valid_sort) === False) {
            $sortorder = $valid_sort['0'];
        } else {
            $sortorder = Req::Post_Safe('sortorder');
        }

        $sortname = in_array(Req::Post_Safe('sortname'), $colom) === False ? $colom['0'] : Req::Post_Safe('sortname');


        $unique = 'id';
        $page = Req::is_Post('page') ? Req::Post_Safe('page') : 1;


        $like = "";
        if (Req::is_Post('query')) {
            $query = Req::Post_Safe('query');
            $qtype = Req::Post_Safe('qtype');
            $f = 'where_like_' . $qtype . '';
            $this->user->$f('%' . $query . '%');
            //$like = " WHERE " . $qtype . " LIKE '%" . $query . "%'";
        }

        $join = [
            [
                'table' => [
                    'mod_user', $colom
                ],
                'alias' => [
                    'mod_user_group', ['ad', 'group_id']
                ],
                'condition' => [
                    'mod_user.group_id', 'mod_user_group.group_id'
                ],
                'type' => 'LEFT'
            ]
        ];
        $this->user->table_join($join);
        $this->user->getResult(TRUE, TRUE);


        $total = $this->user->getSize();
        $this->json['total'] = $total;
        $this->json['page'] = $page;

        // If no results found 
        if($total< 1){
            echo json_encode($this->json);
            exit();
        }

        // Offset Value
        $offset = $page != 1 ? (($page-1) * $rp) : NULL;

        $this->user->limit($rp, $offset);
        $this->user->order($sortname, $sortorder);
        $this->user->getResult(TRUE, TRUE);

        // If no results
        if($this->user->getSize()< 1){
            echo json_encode($this->json);
            exit();
        }
        
        foreach ($this->user->toArray() AS $v) {
            $array['id']=$v[$unique];
            $array['cell']=$this->format($v);
            // $array['cell']=array_values($v);
            $this->json['rows'][]=$array;
        }
        echo json_encode($this->json);
    }


    function format($value)
    {
        if ($value['status'] === 'y') {
            $value['status'] = $this->lang['mod_user']['grid']['active'];
        } elseif ($value['status'] === 'n') {
            $value['status'] = $this->lang['mod_user']['grid']['waiting_email_activation'];
        } elseif ($value['status'] === 'a') {
            $value['status'] = $this->lang['mod_user']['grid']['waiting_admin_activation'];
        }
        return array_values($value);
    }
}