<?php

class mod_news_social_jobs_model extends ORM
{
    public $_table = "mod_news_social_jobs";
    public $_pk = "id";
    public $_ai = "id";

    public $id;
    public $news_id;
    public $account_id;
    public $platform;
    public $idempotency_key;
    public $status;
    public $scheduled_at;
    public $attempts;
    public $max_attempts;
    public $next_retry_at;
    public $external_container_id;
    public $external_post_id;
    public $external_post_url;
    public $story_image_path;
    public $worker_id;
    public $heartbeat_at;
    public $last_error_code;
    public $last_error_message;
    public $published_at;
    public $created_at;
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
            'account_id' => 0,
            'platform' => 'x',
            'idempotency_key' => '',
            'status' => 'pending',
            'scheduled_at' => date('Y-m-d H:i:s'),
            'attempts' => 0,
            'max_attempts' => 4,
            'next_retry_at' => null,
            'external_container_id' => null,
            'external_post_id' => null,
            'external_post_url' => null,
            'story_image_path' => null,
            'worker_id' => null,
            'heartbeat_at' => null,
            'last_error_code' => null,
            'last_error_message' => null,
            'published_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }
}
