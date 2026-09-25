<?php
class mod_user_group_model extends ORM
{
    public $_table = "mod_user_group";
    public $_pk = "group_id";
    public $_ai = "group_id";
    public $group_id;
    public $name;
    public $order;
    public $parent_id;
    public $roles;
    public $user_id;

    /**
     * Magic setter to handle dynamic properties
     * This prevents deprecation warnings in PHP 8.3+
     */
    public function __set($name, $value)
    {
        // Check if the property exists in the class
        if (property_exists($this, $name)) {
            $this->$name = $value;
        } else {
            // For dynamic properties, store them in a data array
            $this->data[$name] = $value;
        }
    }

    /**
     * Magic getter to handle dynamic properties
     */
    public function __get($name)
    {
        // Check if the property exists in the class
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        // Check if it exists in the dynamic data array
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return null;
    }

    /**
     * Dynamic data storage for non-declared properties
     */
    private $data = [];

}