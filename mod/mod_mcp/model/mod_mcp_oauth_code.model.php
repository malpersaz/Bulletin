<?php
/**
 * Amed Framework
 * MCP OAuth Authorization Code Model
 * mod/mod_mcp/model/mod_mcp_oauth_code.model.php
 */
class mod_mcp_oauth_code_model extends ORM
{
    public $_table = "mod_mcp_oauth_code";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $code_hash;
    public $client_id;
    public $redirect_uri;
    public $code_challenge;
    public $resource;
    public $user_id;
    public $group_id;
    public $used_at;
    public $expires_at;
    public $created_at;
}
