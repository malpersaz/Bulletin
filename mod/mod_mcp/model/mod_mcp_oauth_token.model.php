<?php
/**
 * Amed Framework
 * MCP OAuth Token Model
 * mod/mod_mcp/model/mod_mcp_oauth_token.model.php
 */
class mod_mcp_oauth_token_model extends ORM
{
    public $_table = "mod_mcp_oauth_token";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $access_token_hash;
    public $refresh_token_hash;
    public $client_id;
    public $resource;
    public $user_id;
    public $group_id;
    public $status;
    public $expires_at;
    public $refresh_expires_at;
    public $last_used_at;
    public $created_at;
}
