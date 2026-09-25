<?php
/**
 * Amed Framework
 * MCP OAuth Client Model
 * mod/mod_mcp/model/mod_mcp_oauth_client.model.php
 */
class mod_mcp_oauth_client_model extends ORM
{
    public $_table = "mod_mcp_oauth_client";
    public $_pk = "id";
    public $_ai = "id";
    public $id;
    public $client_id;
    public $client_name;
    public $redirect_uris;
    public $status;
    public $created_at;
    public $updated_at;
}
