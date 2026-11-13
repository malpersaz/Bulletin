<?php

class datatable_model
{
    public $mod_socialmedia_post_model;
    private $lang;
    public $session;
    public $json;

    public function __construct()
    {
        $this->mod_socialmedia_post_model = Load::model('mod_socialmedia_post');
        $this->lang = Load::lang('mod_socialmedia_post');
        $this->session = Load::lib('Session');
    }

    public function json()
    {
        $validSort = ['asc', 'desc'];
        $columns = ['id', 'name', 'provider', 'media_type', 'image', 'lang', 'created_at', 'status'];

        $rp = Req::is_Post('rp') === true ? (int) Req::Post_Safe('rp') : 10;
        if ($rp < 1) {
            $rp = 10;
        }

        $sortOrder = Req::is_Post('sortorder') === true ? (string) Req::Post_Safe('sortorder') : 'desc';
        if (!in_array($sortOrder, $validSort, true)) {
            $sortOrder = 'desc';
        }

        $sortName = Req::is_Post('sortname') === true ? (string) Req::Post_Safe('sortname') : $columns[0];
        if (!in_array($sortName, $columns, true)) {
            $sortName = $columns[0];
        }

        $unique = 'id';
        $page = Req::is_Post('page') ? max(1, (int) Req::Post_Safe('page')) : 1;

        $moderate = (
            Role::hasPermission('mod_socialmedia_post', 'moderate_edit') === true
            || Role::hasPermission('mod_socialmedia_post', 'moderate_delete') === true
        );

        if (!$moderate) {
            $this->mod_socialmedia_post_model->where_user_id($this->session->get('id'));
        }

        if (Req::is_Post('query')) {
            $query = (string) Req::Post_Safe('query');
            $qtype = (string) Req::Post_Safe('qtype');
            if (in_array($qtype, $columns, true)) {
                $method = !$moderate ? 'where_like_' . $qtype . '_and_user_id' : 'where_like_' . $qtype;
                if (!$moderate) {
                    $this->mod_socialmedia_post_model->$method('%' . $query . '%', $this->session->get('id'));
                } else {
                    $this->mod_socialmedia_post_model->$method('%' . $query . '%');
                }
            }
        }

        $this->mod_socialmedia_post_model->select('id,name,provider,media_type,image,lang,created_at,status')->gR();
        $total = $this->mod_socialmedia_post_model->getSize();
        $this->json['total'] = $total;
        $this->json['page'] = $page;

        if ($total < 1) {
            echo json_encode($this->json);
            exit();
        }

        $offset = $page != 1 ? (($page - 1) * $rp) : null;
        $this->mod_socialmedia_post_model->limit($rp, $offset);
        $this->mod_socialmedia_post_model->order($sortName, $sortOrder);
        $this->mod_socialmedia_post_model->getResult(true, true);

        if ($this->mod_socialmedia_post_model->getSize() < 1) {
            echo json_encode($this->json);
            exit();
        }

        foreach ($this->mod_socialmedia_post_model->toArray() as $row) {
            $item = [
                'id' => $row[$unique],
                'cell' => $this->format($row, $columns),
            ];
            $this->json['rows'][] = $item;
        }

        echo json_encode($this->json);
    }

    private function format($value, $columns)
    {
        $row = [];
        foreach ($columns as $column) {
            if (array_key_exists($column, $value)) {
                $row[] = $value[$column];
            }
        }
        return $row;
    }
}
