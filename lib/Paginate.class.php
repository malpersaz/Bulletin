<?php

/**
 * Amed Framework
 * Class: Paginate
 * File: Paginate.class.php
 * Description: HTML Pagination Helper
 */

class Paginate
{
    public $result = [];
    public $start;
    public $last;
    public $v;
    public $slimit = 3;
    public $n_p_link;
    public $a_link;
    public $link;
    public $total;
    public $page = 1;
    public $limit = 10;
    public $i_link;
    public $i_text = 'Next';
    public $i_p_link;
    public $g_link;
    public $g_text = 'Prev';
    public $g_p_link;

    public function next()
    {
        $this->result['next'] = $this->page != $this->v ? $this->replace($this->i_link, $this->page + 1, $this->i_text) : $this->replace($this->i_p_link, $this->i_text);
    }

    public function prev()
    {
        $this->result['prev'] = $this->page != 1 ? $this->replace($this->g_link, $this->page - 1, $this->g_text) : $this->replace($this->g_p_link, $this->g_text);
    }

    public function page()
    {
        $this->start = $this->page - $this->slimit;
        $this->last = $this->page + $this->slimit;
        if ($this->start < 1) {
            $this->start = 1;
        }
        if ($this->last > $this->v) {
            $this->last = $this->v;
        }
        $output = "";
        if ($this->page - $this->slimit >= 2) {
            $output .= $this->replace($this->link, 1);
        }
        if ($this->page - $this->slimit >= 3) {
            $output .= $this->replace($this->link, 2);
        }

        if ($this->page - $this->slimit > 3) {
            $output .= $this->replace($this->n_p_link, '...');
        }

        for ($i = $this->start; $i <= $this->last; $i++) {
            $output .= ($this->page != $i) ? $this->replace($this->link, $i) : $this->replace($this->a_link, $i);
        }

        if ($this->page + $this->slimit <= $this->v - 3) {
            $output .= $this->replace($this->n_p_link, '...');
        }

        if ($this->page + $this->slimit <= $this->v - 2) {
            $output .= $this->replace($this->link, $this->v - 1);
        }

        if ($this->page + $this->slimit <= $this->v - 1) {
            $output .= $this->replace($this->link, $this->v);
        }
        $this->result['pages'] = $output;
    }

    public function replace($link, $number, $text = NULL)
    {
        $link = str_replace('#page_num#', $number, (string)$link);
        return $text == NULL ? str_replace('#name#', $number, $link) : str_replace('#name#', $text, $link);
    }

    public function run()
    {
        $this->v = ceil($this->total / $this->limit);
        $this->page = $this->page > $this->v ? '1' : $this->page;
        $this->next();
        $this->prev();
        $this->page();
        if ($this->v <= 1) {
            unset($this->result);
        }
    }
}