<?php

class mod_news_social_prefs_model extends ORM
{
    public $_table = "mod_news_social_prefs";
    public $_pk = "id";
    public $_ai = "id";

    public $id;
    public $news_id;
    public $channels_json;
    public $custom_caption;
    public $updated_at;

    /**
     * Get default values for a new record.
     *
     * @return array
     */
    public function getDefaults(): array
    {
        return [
            'news_id' => 0,
            'channels_json' => '[]',
            'custom_caption' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }
}
