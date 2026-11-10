<?php
class mod_event_model extends ORM
{
    public $_table = "mod_event";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $lang;
    public $category_id;
    public $name;
    public $subject;
    public $start_date;
    public $end_date;
    public $status;
    public $is_official_holiday;
    public $holiday_duration;
    public $is_recurring;
    public $slider;
    public $deck;
    public $summary;
    public $content;
    public $location;
    public $participants;
    public $guests;
    public $seo;
    public $title;
    public $keywords;
    public $description;
    public $image;
    public $gallery_id;
    public $file_id;
    public $video;
    public $acl_id;
    public $view_count;
    public $created_at;
    public $updated_at;
    public $user_id;

    /**
     * Get default values for a new record.
     *
     * @return array
     */
    public function getDefaults(): array
    {
        return [
            'category_id' => 0,
            'status' => 'y',
            'is_official_holiday' => 'n',
            'holiday_duration' => 0.0,
            'is_recurring' => 'n',
            'slider' => 'n',
            'deck' => 'n',
            'gallery_id' => 0,
            'file_id' => 0,
            'acl_id' => 0,
            'view_count' => 0,
            'image' => null,
            'video' => null,
            'end_date' => null,
            'summary' => null,
            'location' => null,
            'participants' => null,
            'guests' => null,
            'updated_at' => null,
        ];
    }
}
