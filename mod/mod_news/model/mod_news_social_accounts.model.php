<?php

class mod_news_social_accounts_model extends ORM
{
    public $_table = "mod_news_social_accounts";
    public $_pk = "id";
    public $_ai = "id";

    public $id;
    public $platform;
    public $lang_scope;
    public $account_label;
    public $external_account_id;
    public $credentials_cipher;
    public $post_mode;
    public $auto_share_default;
    public $daily_limit;
    public $is_active;
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
            'platform' => 'x',
            'lang_scope' => '*',
            'account_label' => '',
            'external_account_id' => null,
            'credentials_cipher' => '',
            'post_mode' => 'link_card',
            'auto_share_default' => 'y',
            'daily_limit' => 50,
            'is_active' => 'n',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }
}
