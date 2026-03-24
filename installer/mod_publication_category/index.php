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
            array('name' => '#ENGLISH', 'lang' => 'en', 'parent_id' => '0', 'seo' => '#', 'title' => '#', 'keywords' => '#', 'description' => '#', 'root' => 'y', 'user_id' => '1'),
            array('name' => '#DEUTSCH', 'lang' => 'de', 'parent_id' => '0', 'seo' => '#', 'title' => '#', 'keywords' => '#', 'description' => '#', 'root' => 'y', 'user_id' => '1'),
            array('name' => '#FRANCAIS', 'lang' => 'fr', 'parent_id' => '0', 'seo' => '#', 'title' => '#', 'keywords' => '#', 'description' => '#', 'root' => 'y', 'user_id' => '1'),
            array('name' => '#KURDI', 'lang' => 'ku', 'parent_id' => '0', 'seo' => '#', 'title' => '#', 'keywords' => '#', 'description' => '#', 'root' => 'y', 'user_id' => '1'),
            array('name' => '#سۆرانی', 'lang' => 'ckb', 'parent_id' => '0', 'seo' => '#', 'title' => '#', 'keywords' => '#', 'description' => '#', 'root' => 'y', 'user_id' => '1'),
            array('name' => '#SVENSKA', 'lang' => 'sv', 'parent_id' => '0', 'seo' => '#', 'title' => '#', 'keywords' => '#', 'description' => '#', 'root' => 'y', 'user_id' => '1'),
            array('name' => '#ZAZAKI', 'lang' => 'zza', 'parent_id' => '0', 'seo' => '#', 'title' => '#', 'keywords' => '#', 'description' => '#', 'root' => 'y', 'user_id' => '1'),
        );

        foreach ($records as $record) {
            $name = addslashes($record['name']);
            $this->db->fetch_array("SELECT id FROM mod_publication_category WHERE name='" . $name . "' LIMIT 1");
            if ($this->db->getSize() === 0) {
                $this->oke($record['name'] . ' added');
                $order = isset($record['order']) ? (int) $record['order'] : 1;
                $query = sprintf(
                    "INSERT INTO mod_publication_category (lang, name, parent_id, `order`, root, seo, title, keywords, description, image, menu, acl_id, user_id)
                     VALUES ('%s', '%s', %d, %d, '%s', '%s', '%s', '%s', '%s', %s, %d, %d, %d)",
                    addslashes($record['lang']),
                    $name,
                    (int) $record['parent_id'],
                    $order,
                    addslashes($record['root']),
                    addslashes($record['seo']),
                    addslashes($record['title']),
                    addslashes($record['keywords']),
                    addslashes($record['description']),
                    isset($record['image']) && $record['image'] !== null ? ("'" . addslashes($record['image']) . "'") : 'NULL',
                    isset($record['menu']) ? (int) $record['menu'] : 0,
                    isset($record['acl_id']) ? (int) $record['acl_id'] : 0,
                    (int) $record['user_id']
                );
                if ($this->db->query($query) === FALSE) {
                    $this->error($record['name'] . ' could not be added');
                    return FALSE;
                }
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
