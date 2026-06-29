<?php
/* 
 * Amed Framework
 * @2025
 * mod_settings Module - Admin Controller
 * mod/mod_settings/index.php
 */
class mod_settings_mod extends indexMain
{


    public function __construct()
    {
        parent::__construct();
        // Load required libraries and models
        $this->tema = Load::lib('AdminTemplate');
        $this->db = Load::lib('Database');

        // Load language based on the current interface language.
        // Use the explicit runtime default language when the request does not provide one.
        $default_lang = Kayit::getir('default_lang');
        if (!is_string($default_lang) || $default_lang === '') {
            $default_lang = 'en';
        }
        
        $selected_lang = $this->site['lang'] ?? $default_lang; // Will be updated later
        $this->lang = Load::lang('mod_settings');
        
        $this->session = Load::lib('Session');
        $this->user = Load::model('mod_user.user');

        
        $this->tema->lang($this->lang);
    }

    /**
     * Handles the editing of site settings.
     * Displays a form with settings for all languages and processes updates.
     */
    public function edit($lang = NULL)
    {
        // Check for 'edit' permission for this module
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            return false;
        }

        // Process form submission if POST request
        if (Req::is_Post('edit')) {
            $this->_edit();
        }
        
        // Prepare the view
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_settings']['title'];
        $this->tema->veri('site', $this->site);


        // Fetch all languages for dropdown
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY name ASC");
        $langs = $this->db->getResult()->toArray();
        $this->tema->veri('langs', $langs);

        // Clean the language parameter
        $lang = Req::Url_Clear($lang);

        // Get selected language from URL parameter
        $selected_lang = null;
        if ($lang && !empty($lang)) {
            $selected_lang = $lang;
        } else {
            // For admin settings, prefer the explicit runtime default language.
            $selected_lang = Kayit::getir('default_lang');
            if (!is_string($selected_lang) || $selected_lang === '') {
                $selected_lang = 'en';
            }
            $lang_exists = false;
            foreach ($langs as $lang_item) {
                if ($lang_item['seo'] === $selected_lang) {
                    $lang_exists = true;
                    break;
                }
            }
            // If Türkçe doesn't exist, use first available language
            if (!$lang_exists && !empty($langs)) {
                $selected_lang = $langs[0]['seo'];
            }
        }

        // REMOVED: Do not force interface language to match edited language
        // $this->lang = Load::lang('mod_settings', $selected_lang);
        // $this->tema->lang($this->lang);

        // Verify the selected language exists in available languages
        $lang_exists = false;
        foreach ($langs as $lang) {
            if ($lang['seo'] === $selected_lang) {
                $lang_exists = true;
                break;
            }
        }

        // If language doesn't exist, use first available language
        if (!$lang_exists && !empty($langs)) {
            $selected_lang = $langs[0]['seo'];
        }

        $this->tema->veri('selected_lang', $selected_lang);
        $this->_load_flash_messages();

        // Fetch settings for selected language
        $setting = null;
        if ($selected_lang) {
            $settings_model = Load::model('mod_settings');
            $settings_model->where_lang($selected_lang)->gRo();
            if ($settings_model->getSize() > 0) {
                $settings_model->sync();
                $setting = $settings_model->getResultOne()->toArray();
            }
        }
        $this->tema->veri('setting', $setting);
        
        // Render the template
        $this->tema->tpl('mod_settings/edit');
    }

    /**
     * Handles the social media standalone page
     */
    public function social_media()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            return false;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_settings']['sections']['social_title'] ?? 'Social Media';
        $this->tema->veri('site', $this->site);

        $this->tema->tpl('mod_settings/social_media');
    }

    public function duzenle($lang = NULL)
    {
        return $this->edit($lang);
    }    /**
     * Private method to handle the POST logic for updating settings.
     */
    private function _edit()
    {
        $selected_lang = Req::post('lang');
        if (empty($selected_lang)) {
            $this->_error_message($this->lang['mod_settings']['missing_language']);
            return;
        }

        $draft_payload = isset($_POST['draft_payload']) ? json_decode($_POST['draft_payload'], true) : [];
        if (!is_array($draft_payload)) {
            $draft_payload = [];
        }

        $draft_payload[$selected_lang] = $this->_extract_setting_fields_from_post();

        foreach ($draft_payload as $lang => $values) {
            $lang = Req::Url_Clear($lang);
            if (empty($lang) || !is_array($values)) {
                continue;
            }

            if ($this->_save_setting_fields($lang, $values) === false) {
                return;
            }
        }

        $settings_model = Load::model('mod_settings');
        $settings_model->where_lang($selected_lang)->gRo();
        if ($settings_model->getSize() > 0) {
            $settings_model->sync();
            $setting = $settings_model->getResultOne();
        } else {
            $setting = Load::model('mod_settings');
            $setting->lang = $selected_lang;
        }

        $run_generation = false;
        $source_path = null;

        // Handle cropped symbol base64 upload
        $cropped_symbol = Req::post('cropped_symbol');
        if (!empty($cropped_symbol) && strpos($cropped_symbol, 'data:image/png;base64,') === 0) {
            $data = base64_decode(substr($cropped_symbol, strlen('data:image/png;base64,')));
            $dest_dir = root_dir . 'static' . DS . 'mod_settings' . DS;
            if (!is_dir($dest_dir)) {
                mkdir($dest_dir, 0755, true);
            }
            $symbol_path = $dest_dir . 'logo_symbol.png';
            file_put_contents($symbol_path, $data);

            $setting->logo_symbol = 'logo_symbol.png';
            $this->db->query("UPDATE mod_settings SET logo_symbol = 'logo_symbol.png'");
            $source_path = $symbol_path;
            $run_generation = true;
        }

        // Handle cropped square base64 upload
        $cropped_square = Req::post('cropped_square');
        if (!empty($cropped_square) && strpos($cropped_square, 'data:image/png;base64,') === 0) {
            $data = base64_decode(substr($cropped_square, strlen('data:image/png;base64,')));
            $dest_dir = root_dir . 'static' . DS . 'mod_settings' . DS;
            if (!is_dir($dest_dir)) {
                mkdir($dest_dir, 0755, true);
            }
            $square_path = $dest_dir . 'logo_square.png';
            file_put_contents($square_path, $data);

            $setting->site_icon = 'logo_square.png';
            $this->db->query("UPDATE mod_settings SET site_icon = 'logo_square.png'");

            // Only use square logo as source if symbol was not just uploaded and doesn't exist
            if (!$run_generation) {
                if (empty($setting->logo_symbol) || !file_exists($dest_dir . $setting->logo_symbol)) {
                    $source_path = $square_path;
                    $run_generation = true;
                }
            }
        }

        // If logo_symbol or site_icon already exists, regenerate assets automatically on save
        if (!$run_generation) {
            $dest_dir = root_dir . 'static' . DS . 'mod_settings' . DS;
            if (!empty($setting->logo_symbol) && file_exists($dest_dir . $setting->logo_symbol)) {
                $source_path = $dest_dir . $setting->logo_symbol;
                $run_generation = true;
            } elseif (!empty($setting->site_icon) && file_exists($dest_dir . $setting->site_icon)) {
                $source_path = $dest_dir . $setting->site_icon;
                $run_generation = true;
            }
        }

        if ($run_generation && !empty($source_path)) {
            try {
                $generator = Load::lib('BrandAssetGenerator');
                $generated_fields = $generator->generateFromLocalFile(
                    $source_path,
                    [
                        'title' => isset($setting->title) ? $setting->title : '',
                        'description' => isset($setting->description) ? $setting->description : '',
                    ],
                    $this->_current_asset_files($setting)
                );
                foreach ($generated_fields as $field => $file_name) {
                    $setting->$field = $file_name;
                }
            } catch (Exception $e) {
                $this->_form_error($this->_resolve_asset_generator_error($e->getMessage()));
                return;
            }
        }

        // Handle cropped rect base64 upload
        $cropped_rect = Req::post('cropped_rect');
        if (!empty($cropped_rect) && strpos($cropped_rect, 'data:image/png;base64,') === 0) {
            $data = base64_decode(substr($cropped_rect, strlen('data:image/png;base64,')));
            $dest_dir = root_dir . 'static' . DS . 'mod_settings' . DS;
            if (!is_dir($dest_dir)) {
                mkdir($dest_dir, 0755, true);
            }
            $rect_path = $dest_dir . 'logo.png';
            file_put_contents($rect_path, $data);

            $setting->logo_path = 'logo.png';
            $this->db->query("UPDATE mod_settings SET logo_path = 'logo.png'");
        }

        if ($this->_has_uploaded_file('logo_' . $selected_lang)) {
            try {
                $this->_generate_assets_for_setting($selected_lang, $setting);
            } catch (Exception $e) {
                $this->_form_error($this->_resolve_asset_generator_error($e->getMessage()));
                return;
            }
        }

        $save_result = $settings_model->getSize() > 0 ? $setting->update('lang') : $setting->save();

        if ($save_result) {
            $this->_flash_success_and_redirect($selected_lang, $this->lang['mod_settings']['messages']['success']['updated']);
        } else {
            $this->_form_error($this->lang['mod_settings']['save_error'] . ': ' . $settings_model->db->sql_error());
        }
    }

    private function _extract_setting_fields_from_post()
    {
        return [
            'title' => trim(Req::Post('title')),
            'description' => trim(Req::Post('description')),
            'owner_name' => trim(Req::Post('owner_name')),
            'established_year' => trim(Req::Post('established_year')),
            'phone' => trim(Req::Post('phone')),
            'whatsapp' => trim(Req::Post('whatsapp')),
            'email' => trim(Req::Post('email')),
            'office_address' => trim(Req::Post('office_address')),
            'work_address' => trim(Req::Post('work_address')),
            'map_embed_code' => trim(Req::Post('map_embed_code')),
            'google_search_console' => trim(Req::Post('google_search_console')),
            'google_analytics' => trim(Req::Post('google_analytics')),
            'meta_tags' => trim(Req::Post('meta_tags')),
            'show_logo' => trim(Req::Post('show_logo')),
            'logo_type' => trim(Req::Post('logo_type')),
        ];
    }

    private function _normalize_setting_fields(array $values)
    {
        return [
            'title' => trim((string) ($values['title'] ?? '')),
            'description' => trim((string) ($values['description'] ?? '')),
            'owner_name' => trim((string) ($values['owner_name'] ?? '')),
            'established_year' => trim((string) ($values['established_year'] ?? '')),
            'phone' => trim((string) ($values['phone'] ?? '')),
            'whatsapp' => trim((string) ($values['whatsapp'] ?? '')),
            'email' => trim((string) ($values['email'] ?? '')),
            'office_address' => trim((string) ($values['office_address'] ?? '')),
            'work_address' => trim((string) ($values['work_address'] ?? '')),
            'map_embed_code' => trim((string) ($values['map_embed_code'] ?? '')),
            'google_search_console' => trim((string) ($values['google_search_console'] ?? '')),
            'google_analytics' => trim((string) ($values['google_analytics'] ?? '')),
            'meta_tags' => trim((string) ($values['meta_tags'] ?? '')),
            'show_logo' => (isset($values['show_logo']) && $values['show_logo'] == 'y') ? 'y' : 'n',
            'logo_type' => trim((string) ($values['logo_type'] ?? 'rectangle')),
        ];
    }

    private function _save_setting_fields($lang, array $values)
    {
        $settings_model = Load::model('mod_settings');
        $settings_model->where_lang($lang)->gRo();
        $normalized_values = $this->_normalize_setting_fields($values);
        
        if ($settings_model->getSize() > 0) {
            $settings_model->sync();
            $setting = $settings_model->getResultOne();

            $has_changes = false;
            foreach ($normalized_values as $field => $value) {
                if (trim((string) $setting->$field) !== $value) {
                    $has_changes = true;
                    break;
                }
            }

            if (!$has_changes) {
                return true;
            }
        } else {
            $has_values = false;
            foreach ($normalized_values as $value) {
                if ($value !== '') {
                    $has_values = true;
                    break;
                }
            }

            if (!$has_values) {
                return true;
            }

            $setting = Load::model('mod_settings');
            $setting->lang = $lang;
        }

        foreach ($normalized_values as $field => $value) {
            $setting->$field = $value;
        }

        $save_result = $settings_model->getSize() > 0 ? $setting->update('lang') : $setting->save();
        if ($save_result) {
            // Global synchronization: show_logo and logo_type apply to all languages
            if (isset($normalized_values['show_logo']) || isset($normalized_values['logo_type'])) {
                $global_fields = [];
                if (isset($normalized_values['show_logo'])) {
                    $global_fields['show_logo'] = $normalized_values['show_logo'];
                }
                if (isset($normalized_values['logo_type'])) {
                    $global_fields['logo_type'] = $normalized_values['logo_type'];
                }
                $this->_sync_asset_fields($global_fields);
            }
            
            return true;
        }

        $this->_form_error($this->lang['mod_settings']['save_error'] . ': ' . $settings_model->db->sql_error());
        return false;
    }

    public function ajax_toggle_logo()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $show_logo = Req::post('show_logo');

        if (!in_array($show_logo, ['y', 'n'])) {
            $show_logo = 'n';
        }

        $this->db->query("UPDATE mod_settings SET show_logo = '" . $show_logo . "'");

        $this->_json_response([
            'status' => 'success',
            'message' => $this->lang['mod_settings']['messages']['success']['updated'] ?? 'Setting has been updated',
        ]);
    }

    public function upload_cropped_logo_ajax()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $selected_lang = Req::post('lang');
        $step = intval(Req::post('step'));
        $image_data = Req::post('image');

        if (empty($selected_lang) || empty($image_data) || strpos($image_data, 'data:image/png;base64,') !== 0) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['invalid_params'] ?? 'Invalid parameters.',
            ]);
            return;
        }

        $settings_model = Load::model('mod_settings');
        $settings_model->where_lang($selected_lang)->gRo();
        if ($settings_model->getSize() > 0) {
            $settings_model->sync();
            $setting = $settings_model->getResultOne();
        } else {
            $setting = Load::model('mod_settings');
            $setting->lang = $selected_lang;
        }

        $data = base64_decode(substr($image_data, strlen('data:image/png;base64,')));
        $dest_dir = root_dir . 'static' . DS . 'mod_settings' . DS;
        if (!is_dir($dest_dir)) {
            mkdir($dest_dir, 0755, true);
        }

        $run_generation = false;
        $source_path = null;

        if ($step === 3) {
            // Emblem / Symbol Logo
            $symbol_path = $dest_dir . 'logo_symbol.png';
            file_put_contents($symbol_path, $data);
            $setting->logo_symbol = 'logo_symbol.png';
            $this->db->query("UPDATE mod_settings SET logo_symbol = 'logo_symbol.png'");
            $source_path = $symbol_path;
            $run_generation = true;
        } elseif ($step === 1) {
            // Square Logo
            $square_path = $dest_dir . 'logo_square.png';
            file_put_contents($square_path, $data);
            $setting->site_icon = 'logo_square.png';
            $this->db->query("UPDATE mod_settings SET site_icon = 'logo_square.png'");

            // Priority: Symbol Logo is used if available, otherwise Square Logo
            if (!empty($setting->logo_symbol) && file_exists($dest_dir . $setting->logo_symbol)) {
                $source_path = $dest_dir . $setting->logo_symbol;
            } else {
                $source_path = $square_path;
            }
            $run_generation = true;
        } elseif ($step === 2) {
            // Rectangle Logo
            $rect_path = $dest_dir . 'logo.png';
            file_put_contents($rect_path, $data);
            $setting->logo_path = 'logo.png';
            $this->db->query("UPDATE mod_settings SET logo_path = 'logo.png'");
        }

        $generated_fields = [];
        if ($run_generation && !empty($source_path)) {
            try {
                $generator = Load::lib('BrandAssetGenerator');
                $generated_fields = $generator->generateFromLocalFile(
                    $source_path,
                    [
                        'title' => isset($setting->title) ? $setting->title : '',
                        'description' => isset($setting->description) ? $setting->description : '',
                    ],
                    $this->_current_asset_files($setting)
                );
                foreach ($generated_fields as $field => $file_name) {
                    $setting->$field = $file_name;
                }
            } catch (Exception $e) {
                $this->_json_response([
                    'status' => 'error',
                    'message' => $this->_resolve_asset_generator_error($e->getMessage()),
                ]);
                return;
            }
        }

        $save_result = $settings_model->getSize() > 0 ? $setting->update('lang') : $setting->save();

        if (!$save_result) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['save_error'] ?? 'Save error.',
            ]);
            return;
        }

        $this->_json_response([
            'status' => 'success',
            'message' => $this->lang['mod_settings']['messages']['success']['updated'] ?? 'Setting has been updated.',
            'fields' => $generated_fields,
            'logo_path' => $setting->logo_path,
            'logo_symbol' => $setting->logo_symbol,
            'site_icon' => $setting->site_icon,
        ]);
    }

    public function generate_assets_ajax()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $selected_lang = Req::post('lang');
        if (empty($selected_lang)) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['missing_language'],
            ]);
            return;
        }

        $settings_model = Load::model('mod_settings');
        $settings_model->where_lang($selected_lang)->gRo();
        if ($settings_model->getSize() > 0) {
            $settings_model->sync();
            $setting = $settings_model->getResultOne();
        } else {
            $setting = Load::model('mod_settings');
            $setting->lang = $selected_lang;
        }

        $setting->title = trim(Req::Post('title'));
        $setting->description = trim(Req::Post('description'));

        try {
            $generated_fields = $this->_generate_assets_for_setting($selected_lang, $setting);
        } catch (Exception $e) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->_resolve_asset_generator_error($e->getMessage()),
            ]);
            return;
        }

        if ($settings_model->getSize() > 0) {
            $save_result = $setting->update('lang');
        } else {
            $save_result = $setting->save();
        }

        if (!$save_result) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['save_error'],
            ]);
            return;
        }

        $this->_json_response([
            'status' => 'success',
            'message' => $this->lang['mod_settings']['messages']['success']['assets_generated'],
            'fields' => $generated_fields,
        ]);
    }

    /**
     * Helper to handle image uploads to avoid code duplication
     */
    private function _handle_image_upload($lang, $field, &$setting, $options)
    {
        $input_name = $field . '_' . $lang;
        
        if (isset($_FILES[$input_name]['name']) && !empty($_FILES[$input_name]['name'])) {
            // Delete old file
            if (!empty($setting->$field)) {
                $resim_del = Load::mod('image');
                $resim_del->mod = 'mod_settings';
                $resim_del->url = $setting->$field;
                $resim_del->_delete_files(['/' => []]);
            }
            
            $image = Load::mod('image');
            $image->name = $input_name;
            $image->mod = 'mod_settings';
            $image->custom_name = $field; // e.g. favicon_96 -> favicon_96.png

            try {
                if ($image->_resize($options)) {
                    $setting->$field = $image->url;
                    // Sync to all languages
                    $this->db->query("UPDATE mod_settings SET $field = '" . $image->url . "'");
                } else {
                     $this->_error_message($this->lang_mgmt['management']['logo_upload_error'] . ' (' . $field . ') ' . $image->error);
                }
            } catch (Exception $e) {
                $msg = $e->getMessage();

                 if (strpos($msg, 'invalid_extension|') === 0) {
                    $ext = explode('|', $msg)[1];
                    $this->_error_message($this->lang_mgmt['management']['logo_upload_error'] . $this->lang_mgmt['management']['invalid_image_extension'] . $ext);
                } else {
                    $this->_error_message($this->lang_mgmt['management']['logo_upload_error'] . $msg);
                }
            }
        }
    }

    private function _current_asset_files($setting)
    {
        $get_value = function ($key) use ($setting) {
            if (is_array($setting)) {
                return isset($setting[$key]) ? $setting[$key] : '';
            }
            return isset($setting->$key) ? $setting->$key : '';
        };

        return array_filter([
            $get_value('favicon_path'),
            $get_value('favicon_96'),
            $get_value('apple_touch_icon'),
            $get_value('site_webmanifest'),
            $get_value('manifest_192'),
            $get_value('manifest_512'),
        ]);
    }

    private function _has_uploaded_file($input_name)
    {
        return isset($_FILES[$input_name]['name']) && !empty($_FILES[$input_name]['name']);
    }

    private function _sync_asset_fields(array $fields)
    {
        foreach ($fields as $field => $value) {
            $this->db->query("UPDATE mod_settings SET $field = '" . $value . "'");
        }
    }

    private function _generate_assets_for_setting($selected_lang, &$setting)
    {
        $logo_file_input_name = 'logo_' . $selected_lang;
        if (!isset($_FILES[$logo_file_input_name]['name']) || empty($_FILES[$logo_file_input_name]['name'])) {
            throw new Exception('missing_upload');
        }

        $generator = Load::lib('BrandAssetGenerator');
        $generated_fields = $generator->generateFromUpload(
            $logo_file_input_name,
            [
                'title' => isset($setting->title) ? $setting->title : '',
                'description' => isset($setting->description) ? $setting->description : '',
            ],
            $this->_current_asset_files($setting)
        );

        foreach ($generated_fields as $field => $file_name) {
            $setting->$field = $file_name;
        }

        $this->_sync_asset_fields($generated_fields);
        return $generated_fields;
    }

    private function _resolve_asset_generator_error($code)
    {
        switch ($code) {
            case 'invalid_master_extension':
                return sprintf($this->lang['mod_settings']['invalid_extension'], '.png');
            case 'master_logo_too_small':
                return $this->lang['mod_settings']['asset_generation']['minimum_size'];
            case 'requires_imagick':
                return $this->lang['mod_settings']['asset_generation']['requires_imagick'];
            case 'output_dir_not_writable':
                return $this->lang['mod_settings']['asset_generation']['output_not_writable'];
            case 'missing_upload':
                return $this->lang['mod_settings']['asset_generation']['missing_upload'];
            case 'invalid_uploaded_file':
            default:
                return $this->lang['mod_settings']['generic_error'];
        }
    }

    private function _json_response(array $payload)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
    }

    private function _flash_success_and_redirect($selected_lang, $message)
    {
        if ($this->session->has('mod_settings_flash_success')) {
            $this->session->remove('mod_settings_flash_success');
        }

        if (!empty($message) && method_exists($this->session, 'ekle')) {
            $this->session->ekle('mod_settings_flash_success', $message);
        }

        header('Location: ' . $this->site['saddress'] . 'mod_settings/edit/' . Req::Url_Clear($selected_lang));
        exit;
    }

    private function _load_flash_messages()
    {
        if ($this->session->has('mod_settings_flash_success')) {
            $this->_oke_message($this->session->get('mod_settings_flash_success'));
            $this->session->remove('mod_settings_flash_success');
        }
    }

    // Helper methods for success/error messages
    private function _oke_message($mesaj)
    {
        $this->tema->veri('oke', true);
        $this->tema->veri('mesaj', $mesaj);
    }

    private function _error_message($mesaj)
    {
        $this->tema->veri('hata', true);
        $this->tema->veri('mesaj', $mesaj); // Fixed: Set mesaj variable for the alert
    }

    // New helper for form validation errors (does not hide form)
    private function _form_error($mesaj)
    {
        $this->tema->veri('hatalar', [$mesaj]);
    }

    public function image_remove_image()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            echo $this->lang['mod_settings']['error'] . ': ' . $this->lang['mod_settings']['permission_errors']['settings_edit'];
            return;
        }

        $lang = Req::post('lang');
        $field = Req::post('field');

        if (empty($lang) || !in_array($field, ['logo_path', 'logo_symbol', 'site_icon', 'favicon_path', 'favicon_96', 'apple_touch_icon', 'site_webmanifest', 'manifest_192', 'manifest_512'])) {
            echo $this->lang['mod_settings']['error'] . ': ' . $this->lang['mod_settings']['invalid_params'];
            return;
        }

        $settings_model = Load::model('mod_settings');
        $settings_model->where_lang($lang)->gRo();

        if ($settings_model->getSize() === 0) {
            echo $this->lang['mod_settings']['error'] . ': ' . $this->lang['mod_settings']['settings_not_found'];
            return;
        }

        $settings_model->sync();
        $setting = $settings_model->getResultOne();
        
        $image_path = $setting->$field;

        if (empty($image_path)) {
            echo 'Oke'; // Already gone
            return;
        }
        
        $delete_options = ['/' => []];
        
        // Handle custom deletion for logo_path (Rectangle Logo)
        if ($field === 'logo_path') {
             $file_path = root_dir . 'static' . DS . 'mod_settings' . DS . 'logo.png';
             if (file_exists($file_path)) {
                 @unlink($file_path);
             }
             $setting->logo_path = '';
             $this->db->query("UPDATE mod_settings SET logo_path = ''");
             $setting->update('lang');
             echo 'Oke';
             return;
        }

        // Handle custom deletion for logo_symbol (Logo Symbol / Icon)
        if ($field === 'logo_symbol') {
             $file_path = root_dir . 'static' . DS . 'mod_settings' . DS . 'logo_symbol.png';
             if (file_exists($file_path)) {
                 @unlink($file_path);
             }
             $setting->logo_symbol = '';
             $this->db->query("UPDATE mod_settings SET logo_symbol = ''");
             $setting->update('lang');
             echo 'Oke';
             return;
        }

        // Handle custom deletion for site_icon (Square Logo and all generated icons)
        if ($field === 'site_icon') {
             $generator = Load::lib('BrandAssetGenerator');
             $generator->deleteManagedFiles($this->_current_asset_files($setting->toArray()));
             $asset_fields = [
                 'site_icon',
                 'favicon_path',
                 'favicon_96',
                 'apple_touch_icon',
                 'site_webmanifest',
                 'manifest_192',
                 'manifest_512',
             ];
             foreach ($asset_fields as $asset_field) {
                 $setting->$asset_field = '';
                 $this->db->query("UPDATE mod_settings SET $asset_field = ''");
             }
             $setting->update('lang');
             echo 'Oke';
             return;
        }

        if ($field === 'site_webmanifest') {
             $file_path = root_dir . 'static' . DS . 'mod_settings' . DS . $image_path;
             if (file_exists($file_path)) {
                 @unlink($file_path);
             }
             $setting->$field = '';
             $setting->update('lang');
             $this->db->query("UPDATE mod_settings SET $field = ''");
             echo 'Oke';
             return;
        }

        $image = Load::mod('image');
        $image->mod = 'mod_settings';
        $image->url = $image_path;

        if ($image->_delete_files($delete_options)) {
            $setting->$field = '';
            if ($setting->update('lang')) {
                // Sync deletion to all languages
                $this->db->query("UPDATE mod_settings SET $field = ''");
                echo 'Oke';
            } else {
                echo $this->lang['mod_settings']['error'] . ': ' . $this->lang['mod_settings']['update_error'] . ' ORM: ' . $settings_model->db->sql_error();
            }
        } else {
            $error_msg = !empty($image->error) ? $image->error : $this->lang['mod_settings']['delete_unknown_error'];
            echo $this->lang['mod_settings']['error'] . ': ' . $this->lang['mod_settings']['delete_fail'] . ' ' . $error_msg;
        }
    }
    // --- Social Media Management Endpoints ---

    public function ajax_social_list()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $social_model = Load::model('mod_settings.mod_settings_social');
        $this->db->fetch_array("SELECT * FROM mod_settings_social ORDER BY display_order ASC");
        
        $this->_json_response([
            'status' => 'success',
            'data' => $this->db->getResult()->toArray()
        ]);
    }

    public function ajax_social_save()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $id = (int) Req::post('id');
        $url = trim(Req::post('url'));

        if ($id <= 0) {
             $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['invalid_params']
            ]);
            return;
        }

        $social_model = Load::model('mod_settings.mod_settings_social');
        
        $social_model->where_id($id)->gRo();
        if ($social_model->getSize() === 0) {
             $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['error']
            ]);
            return;
        }
        $social_model->sync();
        $social_model = $social_model->getResultOne();

        $social_model->url = $url;
        
        $result = $social_model->update('id');

        if ($result) {
            $this->_json_response([
                'status' => 'success',
                'message' => $this->lang['mod_settings']['messages']['success']['updated']
            ]);
        } else {
             $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['error'] . ' ' . $social_model->db->sql_error()
            ]);
        }
    }



    public function ajax_social_toggle_status()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        $id = (int) Req::post('id');
        $is_active = Req::post('is_active') === 'y' ? 'y' : 'n';

        if ($id <= 0) {
             $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['invalid_params']
            ]);
            return;
        }

        $this->db->query("UPDATE mod_settings_social SET is_active = '{$is_active}' WHERE id = {$id}");

        $this->_json_response([
            'status' => 'success',
            'message' => $this->lang['mod_settings']['messages']['success']['updated']
        ]);
    }

    public function ajax_social_reorder()
    {
        if ($this->user->hasPermission('mod_settings', 'edit', $this->lang['mod_settings']['permission_errors']['settings_edit'], 1) === false) {
            $this->_json_response([
                'status' => 'error',
                'message' => $this->lang['mod_settings']['permission_errors']['settings_edit'],
            ]);
            return;
        }

        // We expect orders to be an array of associative arrays with id and order.
        // E.g., json string or directly array from post depends on client side submission.
        // Assuming client sends it as an array of objects `[{id: 1, order: 1}, ...]` via JSON payload or regular array.
        $orders = Req::post('orders'); 
        
        // If it's a JSON string, decode it.
        if (is_string($orders)) {
            $orders = json_decode($orders, true);
        }

        if (!empty($orders) && is_array($orders)) {
            foreach ($orders as $item) {
                $id = (int) ($item['id'] ?? 0);
                $order = (int) ($item['order'] ?? 0);
                if ($id > 0) {
                     $this->db->query("UPDATE mod_settings_social SET display_order = {$order} WHERE id = {$id}");
                }
            }
        }

        $this->_json_response([
            'status' => 'success',
            'message' => $this->lang['mod_settings']['messages']['success']['updated']
        ]);
    }
}
