<?php

class mod_publication_category extends installerbase implements installerbaseinterface
{
    public $tablolar;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_publication_category';
        $this->tablolar = array(
            'mod_publication_category'
        );
        $this->rolls = array(
            'add', 'edit', 'delete', 'list', 'moderate_edit', 'moderate_delete'
        );
    }

    public function install()
    {
        if ($this->create_tables() === FALSE) {
            return FALSE;
        }
        if ($this->insert_mods() === FALSE) {
            return FALSE;
        }
        if ($this->insert_roll() === FALSE) {
            return FALSE;
        }
        if ($this->add_categories() === FALSE) {
            return FALSE;
        }
        return TRUE;
    }

    public function add_categories()
    {
        $records = array(
            array('name' => '#DEUTSCH', 'lang' => 'de', 'parent_id' => '0', 'order' => '1', 'root' => 'y', 'seo' => '#', 'title' => '#', 'keywords' => '#', 'description' => '#', 'user_id' => '1'),
            array('name' => '#ENGLISH', 'lang' => 'en', 'parent_id' => '0', 'order' => '1', 'root' => 'y', 'seo' => '#', 'title' => '#', 'keywords' => '#', 'description' => '#', 'user_id' => '1'),
            array('name' => '#FRANCAIS', 'lang' => 'fr', 'parent_id' => '0', 'order' => '1', 'root' => 'y', 'seo' => '#', 'title' => '#', 'keywords' => '#', 'description' => '#', 'user_id' => '1'),
            array('name' => '#KURDÎ', 'lang' => 'ku', 'parent_id' => '0', 'order' => '1', 'root' => 'y', 'seo' => '#', 'title' => '#', 'keywords' => '#', 'description' => '#', 'user_id' => '1'),
            array('name' => '#سۆرانی', 'lang' => 'ckb', 'parent_id' => '0', 'order' => '1', 'root' => 'y', 'seo' => '#', 'title' => '#', 'keywords' => '#', 'description' => '#', 'user_id' => '1'),
            array('name' => '#SVENSKA', 'lang' => 'sv', 'parent_id' => '0', 'order' => '1', 'root' => 'y', 'seo' => '#', 'title' => '#', 'keywords' => '#', 'description' => '#', 'user_id' => '1'),
            array('name' => '#ZAZAKÎ', 'lang' => 'zza', 'parent_id' => '0', 'order' => '1', 'root' => 'y', 'seo' => '#', 'title' => '#', 'keywords' => '#', 'description' => '#', 'user_id' => '1'),
        );

        foreach ($records as $record) {
            $name = addslashes($record['name']);
            $this->db->fetch_array("SELECT id FROM mod_publication_category WHERE name='" . $name . "' AND lang='" . addslashes($record['lang']) . "' LIMIT 1");
            if ($this->db->getSize() === 0) {
                $query = sprintf(
                    "INSERT INTO mod_publication_category (lang, name, parent_id, `order`, root, seo, title, keywords, description, image, menu, acl_id, user_id)
                     VALUES ('%s', '%s', %d, %d, '%s', '%s', '%s', '%s', '%s', NULL, 0, 0, %d)",
                    addslashes($record['lang']),
                    $name,
                    (int) $record['parent_id'],
                    (int) $record['order'],
                    addslashes($record['root']),
                    addslashes($record['seo']),
                    addslashes($record['title']),
                    addslashes($record['keywords']),
                    addslashes($record['description']),
                    (int) $record['user_id']
                );

                if ($this->db->query($query) === FALSE) {
                    $this->error($record['name'] . ' could not be added');
                    return FALSE;
                }
                $this->oke($record['name'] . ' added');
            } else {
                $this->warning($record['name'] . ' already exists');
            }
        }

        return TRUE;
    }

    public function uninstall()
    {
        $this->remove_tables();
        $this->remove_mods();
        $this->remove_rool();
        $this->remove_perm();
        return TRUE;
    }
}
