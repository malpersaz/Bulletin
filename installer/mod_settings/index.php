<?php

class mod_settings extends installerbase implements installerbaseinterface
{
    public $tablolar;
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->mod = 'mod_settings';
        $this->tablolar = array(
            'mod_settings',
            'settings',
            'mod_settings_social'
        );
        $this->rolls = array(
            'list', 'edit'
        );
    }

    public function install()
    {
        // Create Tables
        if ($this->create_tables() === FALSE) {
            return FALSE;
        }
        
        // Add Module
        if ($this->insert_mods() === FALSE) {
            return FALSE;
        }
        // Add Roles and Permissions
        if ($this->insert_roll() === FALSE) {
            return FALSE;
        }
        // Add Default Settings
        if ($this->add_default_settings() === FALSE) {
            return FALSE;
        }
        // Add Example Social Media Platforms
        if ($this->add_default_social() === FALSE) {
            return FALSE;
        }

        return TRUE;
    }
    
    private function add_default_settings()
    {
        $projectSetup = installer_get_project_setup_state();
        $projectName = trim((string) $projectSetup['project_name']);
        $projectDescription = trim((string) $projectSetup['project_description']);
        $projectOwnerName = trim((string) $projectSetup['project_owner_name']);
        $projectEstablishedYear = trim((string) $projectSetup['project_established_year']);
        $projectPhone = trim((string) $projectSetup['project_phone']);
        $projectWhatsapp = trim((string) $projectSetup['project_whatsapp']);
        $projectEmail = trim((string) $projectSetup['project_email']);
        $projectOfficeAddress = trim((string) $projectSetup['project_office_address']);
        $projectWorkAddress = trim((string) $projectSetup['project_work_address']);

        // Fetch languages dynamically from the database
        $lang_query = $this->db->fetch_array("SELECT name, seo FROM `lang` ");
        if ($this->db->getSize() === 0) {
            $this->warning("No languages found in the system. 'lang' table is empty. Settings cannot be added.");
            return true; // Stop if no languages are defined
        }
        $langs = $this->db->getResult()->toArray();

        $this->model = Load::model('mod_settings');

        foreach ($langs as $lang) {
            // Check if settings for this language already exist
            $this->model->where_lang($lang['seo'])->getResult(TRUE, TRUE);
            if ($this->model->getSize() === 0) {
                $this->model->lang = $lang['seo'];
                $this->model->title = $projectName !== '' ? $projectName : installer_site_build_name();
                $this->model->description = $projectDescription;
                $this->model->owner_name = $projectOwnerName;
                $this->model->established_year = $projectEstablishedYear !== '' ? $projectEstablishedYear : NULL;
                $this->model->phone = $projectPhone;
                $this->model->whatsapp = $projectWhatsapp;
                $this->model->email = $projectEmail;
                $this->model->office_address = $projectOfficeAddress;
                $this->model->work_address = $projectWorkAddress;
                
                $this->model->map_embed_code = NULL; // Default to NULL
                $this->model->google_search_console = NULL; // Default to NULL
                $this->model->google_analytics = NULL; // Default to NULL
                $this->model->meta_tags = NULL; // Default to NULL
                $this->model->show_logo = 'y'; // Default to y
                $this->model->logo_path = 'logo.png';
                $this->model->favicon_path = 'favicon.ico';
                $this->model->favicon_96 = 'favicon-96x96.png';
                $this->model->apple_touch_icon = 'apple-touch-icon.png';
                $this->model->site_webmanifest = 'site.webmanifest';
                $this->model->manifest_192 = 'web-app-manifest-192x192.png';
                $this->model->manifest_512 = 'web-app-manifest-512x512.png';
                $this->model->user_id = 1;

                if ($this->model->save()) {
                    $this->oke("Default settings added for language: " . $lang['name']);
                } else {
                    $this->error("Error occurred while adding settings for language: " . $lang['name']);
                    return FALSE;
                }
            } else {
                $this->warning("Settings already exist for language: " . $lang['name']);
            }
        }
        return TRUE;
    }

    private function add_default_social()
    {
        $platforms = array(
            array(
                'platform' => 'twitter',
                'url' => 'https://twitter.com/akmbusiness',
                'icon_class' => 'bi bi-twitter-x',
                'display_order' => 1
            ),
            array(
                'platform' => 'facebook',
                'url' => 'https://facebook.com/akmbusiness',
                'icon_class' => 'bi bi-facebook',
                'display_order' => 2
            ),
            array(
                'platform' => 'instagram',
                'url' => 'https://instagram.com/akmbusiness',
                'icon_class' => 'bi bi-instagram',
                'display_order' => 3
            ),
            array(
                'platform' => 'linkedin',
                'url' => 'https://linkedin.com/company/akmbusiness',
                'icon_class' => 'bi bi-linkedin',
                'display_order' => 4
            ),
            array(
                'platform' => 'youtube',
                'url' => 'https://youtube.com/@akmbusiness',
                'icon_class' => 'bi bi-youtube',
                'display_order' => 5
            ),
            array(
                'platform' => 'spotify',
                'url' => 'https://open.spotify.com/artist/akmbusiness',
                'icon_class' => 'bi bi-spotify',
                'display_order' => 6
            ),
            array(
                'platform' => 'tiktok',
                'url' => 'https://tiktok.com/@akmbusiness',
                'icon_class' => 'bi bi-tiktok',
                'display_order' => 7
            )
        );

        $this->model = Load::model('mod_settings.mod_settings_social'); // Load the social model

        foreach ($platforms as $platform) {
            $this->model->where_platform($platform['platform'])->getResult(TRUE, TRUE);
            if ($this->model->getSize() === 0) {
                foreach ($platform as $key => $value) {
                    $this->model->$key = $value;
                }
                $this->model->is_active = 'y';
                $this->model->user_id = 1; // Assuming default user ID

                if ($this->model->save()) {
                    $this->oke("Platform added: " . $platform['platform']);
                } else {
                    $this->error("Error occurred while adding platform: " . $platform['platform']);
                    return FALSE;
                }
            } else {
                $this->warning("Platform already exists: " . $platform['platform']);
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
