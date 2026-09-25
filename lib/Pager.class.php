<?php

class Pager
{
    // Total item count e.g. 300
    public $total_item;
    // Page limit e.g. 10
    public $page_limit;
    // Number of pagination links to display e.g. 10
    public $pager_limit;
    // Active page e.g. 7
    public $page;
    // Total page count calculated from $total_item / $page_limit
    public $total_page;
    // Result
    private $result;

    public function call()
    {
        $this->total_page = ceil($this->total_item / $this->page_limit);
        $start = $this->page > $this->pager_limit ? $this->page - ceil($this->pager_limit) : 2;

        $this->result[] = 1;
        for ($i = $start; $i < $start + $this->pager_limit * 2; $i++) {
            if (($i < $this->total_page and $i >= 2) && !in_array($i, $this->result)) {
                $this->result[] = $i;
            }
        }

        $this->result[] = $this->total_page;
        $this->result = array_unique($this->result);
        sort($this->result);

        $last = 0;
        $e = [];
        foreach ($this->result as $res) {

            if ($res - $last > 1) {
                $e[] = '...';
                $e[] = $res;
            } else {
                $e[] = $res;
            }

            $last = $res;
        }
        $this->result = $e;
    }

    public function check()
    {
        $this->call();

        if ($this->total_item === 0) {
            return TRUE;
        }
        if ($this->page > $this->total_page) {
            return FALSE;
        }
        return TRUE;
    }

    public function Result()
    {
        if (count($this->result) <= 1) {
            return FALSE;
        }

        if ($this->total_item === 0) {
            return FALSE;
        }
        return $this->result;
    }
}
