<?php
class mod_news_model extends ORM
{
    public $_table = "mod_news";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $lang;
    public $name;
    public $category_id;
    public $status;
    public $slider;
    public $created_at;
    public $updated_at;
    public $deck;
    public $summary;
    public $content;
    public $seo;
    public $title;
    public $keywords;
    public $description;
    public $gallery_id;
    public $file_id;
    public $image;
    public $acl_id;
    public $video;
    public $view_count;
    public $user_id;

    /**
     * Get default values for a new record.
     *
     * @return array
     */
    public function getDefaults(): array
    {
        return [
            'status' => 'y',
            'slider' => 'n',
            'deck' => 'n',
            'gallery_id' => 0,
            'file_id' => 0,
            'acl_id' => 0,
            'view_count' => 0,
            'image' => null,
            'video' => null,
            'summary' => null,
            'updated_at' => null,
        ];
    }
}

