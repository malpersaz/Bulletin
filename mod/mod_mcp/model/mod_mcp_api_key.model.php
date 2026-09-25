<?php
/**
 * Amed Framework
 * MCP API Key Model
 * mod/mod_mcp/model/mod_mcp_api_key.model.php
 */
class mod_mcp_api_key_model extends ORM
{
    public $_table = "mod_mcp_api_key";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $name;
    public $api_key_hash;
    public $key_prefix;
    public $group_id;
    public $user_id;
    public $status;
    public $last_used_at;
    public $created_at;
    public $updated_at;
}
