<?php

/**
 * BaseModuleController - Base class for all module controllers
 * Eliminates code duplication across 50+ modules
 * 
 * Features:
 * - Automatic CRUD operations: add(), edit(), list(), delete()
 * - Special field processing via $special_fields configuration
 * - Image upload and management via $image_fields configuration
 * - Callback system: beforeSave(), afterSave(), beforeUpdate(), afterUpdate(), beforeDelete(), afterDelete()
 * - DataTable integration via getDatatableColumns()
 * - Menu integration via $menu_url_pattern
 * - Automatic data loading via $need_simple_data, $need_nested_data, $need_content_refs
 * - Gallery and file management via $has_gallery, $has_files
 * 
 * Amed Framework
 * @author Refactored for better maintainability
 */

abstract class BaseModuleController
{
    protected $module_name;
    protected $model_name;
    protected $has_gallery = false;
    protected $has_files = false;

    // Flexible image fields configuration
    protected $image_fields = [
        // Example: ['name' => 'image', 'sizes' => [...]]
    ];

    // Callback configuration
    protected $menu_url_pattern = null;        // e.g., '{lang}/bsk/{id}-{name|sefurl}' - if set, enables menu integration
    
    /**
     * Callback system for custom business logic
     * Each callback takes a model as parameter and must return the model
     * Callbacks can return false to cancel operations (beforeSave/beforeUpdate only)
     * 
     * Override these methods in child classes to implement custom logic:
     * 
     * protected function beforeSave($model) {
     *     // Custom logic before save (data processing, validation, etc.)
     *     $model->custom_field = 'processed_value';
     *     $model->created_at = NOW;
     *     return $model; // Must return model or false to cancel
     * }
     * 
     * protected function afterSave($model) {
     *     // Custom logic after successful save (notifications, cache updates, etc.)
     *     // Send email notifications, update search indexes, etc.
     *     return $model; // Must return model
     * }
     * 
     * protected function beforeUpdate($model, $id) {
     *     // Custom logic before update (data processing, validation, etc.)
     *     $model->updated_at = NOW;
     *     return $model; // Must return model or false to cancel
     * }
     * 
     * protected function afterUpdate($model, $id) {
     *     // Custom logic after successful update (cache invalidation, notifications, etc.)
     *     // Clear cache, send notifications, update related records, etc.
     *     return $model; // Must return model
     * }
     * 
     * protected function beforeDelete($model, $id) {
     *     // Custom logic before delete (validation, backup, etc.)
     *     // Check if record can be deleted, create backup, etc.
     *     return $model; // Must return model or false to cancel
     * }
     * 
     * protected function afterDelete($id) {
     *     // Custom logic after successful delete (cleanup, notifications, etc.)
     *     // Delete related files, send notifications, clear cache, etc.
     *     return true; // Must return true
     * }
     */

    /**
     * Default callback methods - override in child classes for custom logic
     * These methods are called automatically during CRUD operations
     */

    /**
     * Called before saving a new record
     * Override in child classes to add custom logic before save
     * 
     * @param object $model The model object being saved
     * @return object|false Return the modified model or false to cancel operation
     */
    protected function beforeSave($model)
    {
        return $model;
    }

    /**
     * Called after successfully saving a new record
     * Override in child classes to add custom logic after save
     * 
     * @param object $model The model object that was saved
     * @return object Return the model object
     */
    protected function afterSave($model)
    {
        return $model;
    }

    /**
     * Called before updating an existing record
     * Override in child classes to add custom logic before update
     * 
     * @param object $model The model object being updated
     * @param int $id The ID of the record being updated
     * @return object|false Return the modified model or false to cancel operation
     */
    protected function beforeUpdate($model, $id)
    {
        return $model;
    }

    /**
     * Called after successfully updating an existing record
     * Override in child classes to update custom logic after update
     * 
     * @param object $model The model object that was updated
     * @param int $id The ID of the record that was updated
     * @return object Return the model object
     */
    protected function afterUpdate($model, $id)
    {
        return $model;
    }

    /**
     * Called before deleting a record
     * Override in child classes to add custom logic before delete
     * 
     * @param object $model The model object being deleted
     * @param int $id The ID of the record being deleted
     * @return object|false Return the model or false to cancel operation
     */
    protected function beforeDelete($model, $id)
    {
        return $model;
    }

    /**
     * Called after successfully deleting a record
     * Override in child classes to add custom logic after delete
     * 
     * @param int $id The ID of the record that was deleted
     * @return bool Return true for success
     */
    protected function afterDelete($id)
    {
        return true;
    }

    // Simple extensible configuration - modules just declare what data they need
    protected $need_simple_data = [];    // ['table_name' => 'variable_name', 'table_name2' => 'variable_name2']
    protected $need_nested_data = [];    // ['model_name' => 'variable_name', 'model_name2' => 'variable_name2'] 
    protected $need_content_refs = [];   // ['table_name' => ['var1', 'var2'], 'table_name2' => ['var3']]




    public $tema;
    public $db;
    public $site;
    public $lang;
    public $session;
    public $user;
    public $val;
    public $mod;
    public $model;
    public $options;
    public $selected;
    public function __construct($module_name = null)
    {
        // Auto-detect module name if not provided
        if ($module_name === null) {
            $class_name = get_class($this);
            $this->module_name = str_replace('_mod', '', $class_name);
        } else {
            $this->module_name = $module_name;
        }

        $this->model_name = $this->module_name;

        // Common initialization that every module does
        $this->tema = Load::lib('AdminTemplate');
        $this->db = Load::lib('Database');
        $this->site = Kayit::getir('site_config');
        $this->lang = Load::lang($this->module_name);
        $this->tema->lang($this->lang);
        $this->session = Load::lib('Session');
        $this->user = Load::model('mod_user.user');
    }

    /**
     * Define special fields that need custom processing
     * Configuration format: ['field_name' => ['type' => 'field_type', 'options' => ...]]
     * 
     * Available field types:
     * - 'html' => Raw HTML content (uses Req::Post() + Helper::clear_html())
     * - 'now' => Current timestamp (sets field to NOW)
     * - 'user_id' => Current user ID (sets field to session user ID)
     * - 'custom' => Custom processing (requires 'callback' option)
     * - 'skip' => Skip field processing (let callbacks handle it)
     * - 'safe' => Safe processing (default - Req::Post_Safe())
     * 
     * Example:
     * protected $special_fields = [
     *     'content' => ['type' => 'html'],
     *     'created_at' => ['type' => 'now'],
     *     'author_id' => ['type' => 'user_id'],
     *     'custom_field' => ['type' => 'custom', 'callback' => 'processCustomField'],
     *     'skip_field' => ['type' => 'skip']
     * ];
     */
    protected $special_fields = [];

    /**
     * Get image resize options for a specific field
     * Uses image_fields configuration if available, otherwise falls back to default
     */
    protected function getImageOptions($field_name = null)
    {
        // If field name provided and exists in image_fields config, use that
        if ($field_name && !empty($this->image_fields)) {
            foreach ($this->image_fields as $field_config) {
                if (isset($field_config['name']) && $field_config['name'] === $field_name) {
                    if (isset($field_config['sizes'])) {
                        return $field_config['sizes'];
                    }
                }
            }
        }

        // Fallback to default options (can be overridden in child classes)
        return [
            '/' => [
                'width' => 200,
                'height' => null,
                'option' => 'width',
            ],
            '/thumb' => [
                'width' => 100,
                'height' => null,
                'option' => 'width',
            ],
        ];
    }

    /**
     * Get additional template data for add/edit forms
     * Automatically loads data based on module configuration
     */
    protected function getFormData()
    {
        $data = [];

        // Load simple table data - just SELECT * FROM table ORDER BY id DESC (or seo DESC for lang)
        foreach ($this->need_simple_data as $table => $variable_name) {
            $order_field = ($table === 'lang') ? 'seo DESC' : 'id DESC';
            $this->db->fetch_array("SELECT * FROM `{$table}` ORDER BY {$order_field}");
            $data[$variable_name] = $this->db->getResult()->toArray();
        }

        // Load nested hierarchical data - Load model, Helper::nested with 'parent_id', 'id'
        foreach ($this->need_nested_data as $model_name => $variable_name) {
            $nested_model = Load::model($model_name);
            $nested_model->order('order', 'ASC')->all();
            $nested_result = Helper::nested($nested_model->toArray(), 'parent_id', 'id');
            $data[$variable_name] = $nested_result;
        }

        // Load content references - same table loaded multiple times with different variable names
        foreach ($this->need_content_refs as $table_name => $variable_names) {
            foreach ($variable_names as $variable_name) {
                $this->db->fetch_array("SELECT * FROM `{$table_name}` ORDER BY id DESC");
                $data[$variable_name] = $this->db->getResult()->toArray();
            }
        }

        return $data;
    }

    /**
     * Common add functionality
     */
    public function add()
    {
        // Check permission
        if ($this->user->hasPermission($this->module_name, 'add', $this->lang[$this->module_name]['role_errors']['add'], 1) === false) {
            return false;
        }

        if (Req::is_Post('add')) {
            $this->_add();
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang[$this->module_name]['add'];
        $this->tema->tpl($this->module_name . '/add');
        $this->tema->veri('site', $this->site);

        // Set additional form data
        $form_data = $this->getFormData();
        foreach ($form_data as $key => $value) {
            $this->tema->veri($key, $value);
        }
    }

    /**
     * Common edit functionality
     */
    public function edit($id = null)
    {
        // Check permission
        if ($this->user->hasPermission($this->module_name, 'edit', $this->lang[$this->module_name]['role_errors']['edit'], 1) === false) {
            return false;
        }

        $model = Load::model($this->model_name);
        $id = Req::Url_Clear($id);

        if ($model->exists_id($id) === false) {
            $this->_error($this->lang[$this->module_name]['errors']['record']);
            Run::import('management', '_index');
            $this->tema->tpl($this->module_name . '/edit');
            return;
        }

        $d = $model->getResultOne()->toArray();

        // Check moderation permission
        if ($this->user->checkPermission($this->module_name, 'moderate_edit', $d['user_id'], $this->lang[$this->module_name]['role_errors']['moderate_edit'], 1) === false) {
            return false;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id);
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang[$this->module_name]['edit'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        $this->tema->tpl($this->module_name . '/edit');

        // Set additional form data
        $form_data = $this->getFormData();
        foreach ($form_data as $key => $value) {
            $this->tema->veri($key, $value);
        }
    }

    /**
     * Common list functionality
     */
    public function list()
    {
        // Check permission
        if ($this->user->hasPermission($this->module_name, 'list', $this->lang[$this->module_name]['role_errors']['list'], 1) === false) {
            return false;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang[$this->module_name]['list'];
        $this->tema->tpl($this->module_name . '/list');
        $this->tema->veri('site', $this->site);

        // Set additional form data for the integrated wizard
        $form_data = $this->getFormData();
        foreach ($form_data as $key => $value) {
            $this->tema->veri($key, $value);
        }
    }

    /**
     * Common delete functionality
     */
    public function delete()
    {
        // Check permission
        if ($this->user->hasPermission($this->module_name, 'delete', $this->lang[$this->module_name]['role_errors']['delete']) === false) {
            return false;
        }

        if (Req::is_Post('delete')) {
            if ($this->_delete(Req::Post_Safe('delete'))) {
                $this->echoSuccessResponse();
            } else {
                echo 'ERROR';
            }
        } else {
            exit($this->lang[$this->module_name]['errors']['record']);
        }
    }

    /**
     * Common image removal functionality
     */
    public function image_remove_image()
    {
        if (empty($this->image_fields)) {
            echo 'This module does not have an image feature.';
            return false;
        }

        // Check permission
        if ($this->user->hasPermission($this->module_name, 'edit', $this->lang[$this->module_name]['role_errors']['edit'], 1) === false) {
            return false;
        }

        $id = intval(Req::Post_Safe('id'));
        $model = Load::model($this->model_name);
        $model->where_id($id)->gRo();

        if ($model->getSize() === 0) {
            echo $this->lang[$this->module_name]['errors']['record'];
            return false;
        }

        $model->sync();

        // Check moderation permission
        if ($this->user->checkPermission($this->module_name, 'moderate_edit', $model->user_id, $this->lang[$this->module_name]['role_errors']['moderate_edit']) === false) {
            return false;
        }

        // Process all image fields
        foreach ($this->image_fields as $field_config) {
            $field_name = $field_config['name'];

            if ($model->$field_name != '') {
                // Use image module if sizes are provided, otherwise use ImageService
                if (isset($field_config['sizes'])) {
                    // Use image module for thumbnail deletion
                    $image = Load::mod('image');
                    $image->mod = $this->module_name;
                    $image->url = $model->$field_name;
                    $options = $this->getImageOptions($field_name);

                    if ($image->_delete_files($options)) {
                        $model->$field_name = '';
                        $model->update('id');
                    } else {
                        echo $image->error;
                        return false;
                    }
                } else {
                    // Use ImageService for standard deletion
                    $imageService = Load::lib('ImageService');
                    if ($imageService->deleteImage($this->module_name, $model->$field_name, $this->getImageOptions($field_name))) {
                        $model->$field_name = '';
                        $model->update('id');
                    } else {
                        echo $imageService->getLastError();
                        return false;
                    }
                }
            }
        }

        echo 'Success';
    }

    /**
     * Process field data for add/edit operations
     * By default, all POST fields are processed as 'safe'
     * Only fields in $this->special_fields get special processing
     */
    protected function processFieldData($model)
    {
        // Process all POST fields as 'safe' by default
        foreach ($_POST as $field => $value) {
            // Skip system fields
            if (in_array($field, ['add', 'edit', 'delete'])) {
                continue;
            }

            // Check if this field needs special processing
            if (isset($this->special_fields[$field])) {
                $config = $this->special_fields[$field];
                switch ($config['type']) {
                    case 'html':
                        $model->$field = Req::Post($field);
                        $model->$field = Helper::clear_html($model->$field);
                        break;
                    case 'now':
                        $model->$field = NOW;
                        break;
                    case 'user_id':
                        $model->$field = $this->session->get('id');
                        break;
                    case 'custom':
                        if (isset($config['callback'])) {
                            if (is_callable($config['callback'])) {
                                $model->$field = $config['callback']();
                            } elseif (method_exists($this, $config['callback'])) {
                                $model->$field = $this->{$config['callback']}();
                            }
                        }
                        // Skip setting field - let callbacks handle it
                        continue 2;
                    case 'skip':
                        // Skip this field completely - let callbacks handle it
                        continue 2;
                    default:
                        $model->$field = Req::Post_Safe($field);
                }
            } else {
                // Default: everything is 'safe'
                $model->$field = Req::Post_Safe($field);
            }
        }

        // Handle special automatic fields for add operations
        if (!isset($model->id)) {
            // Add operation - set user_id and created_at automatically
            if (!isset($this->special_fields['user_id'])) {
                $model->user_id = $this->session->get('id');
            }
            if (!isset($this->special_fields['created_at']) && isset($_POST['created_at'])) {
                // If created_at is in POST for add operation, set it to NOW
                $model->created_at = NOW;
            }
        }
    }

    /**
     * Handle image upload - can be overridden for custom image handling
     */
    protected function handleImageUpload($model, $field_name = null, $options = null)
    {
        // If no image fields configured, skip image handling
        if (empty($this->image_fields)) {
            return true;
        }

        // If specific field provided, handle that field
        if ($field_name) {
            $options = $options ?: $this->getImageOptions($field_name);

            // Find the field configuration
            $field_config = null;
            foreach ($this->image_fields as $config) {
                if ($config['name'] === $field_name) {
                    $field_config = $config;
                    break;
                }
            }

            // Use image module if sizes are provided, otherwise use ImageService  
            if ($field_config && isset($field_config['sizes'])) {
                return $this->handleImageUploadWithOptions($model, $field_name, $options);
            } else {
                return $this->handleImageUploadStandard($model, $field_name, $options);
            }
        }

        // If no field name provided, handle all image fields
        foreach ($this->image_fields as $field_config) {
            $field_name = $field_config['name'];
            $options = $this->getImageOptions($field_name);

            // Use image module if sizes are provided, otherwise use ImageService
            if (isset($field_config['sizes'])) {
                if (!$this->handleImageUploadWithOptions($model, $field_name, $options)) {
                    return false;
                }
            } else {
                if (!$this->handleImageUploadStandard($model, $field_name, $options)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Standard image upload using ImageService
     */
    protected function handleImageUploadStandard($model, $field_name, $options)
    {
        if (isset($_FILES[$field_name]['name']) && $_FILES[$field_name]['name'] != '') {
            $imageService = Load::lib('ImageService');
            $result = $imageService->uploadImage($this->module_name, $field_name, $options);

            if ($result) {
                $model->$field_name = $imageService->getUploadedUrl();
                return true;
            } else {
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse([
                        'status' => 'error',
                        'message' => $imageService->getLastError()
                    ]);
                }
                $this->_error_message($imageService->getLastError());
                return false;
            }
        } else {
            if (isset($model->$field_name)) {
                unset($model->$field_name);
            }
        }

        return true;
    }

    /**
     * Image upload with thumbnail using existing image module
     */
    protected function handleImageUploadWithOptions($model, $field_name, $options)
    {
        if (isset($_FILES[$field_name]['name']) && $_FILES[$field_name]['name'] != '') {
            $image = Load::mod('image');
            $image->mod = $this->module_name;
            $image->name = $field_name;

            if ($image->_resize($options)) {
                $model->$field_name = $image->url;
                return true;
            } else {
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse([
                        'status' => 'error',
                        'message' => $image->error
                    ]);
                }
                $this->_error_message($image->error);
                return false;
            }
        } else {
            if (isset($model->$field_name)) {
                unset($model->$field_name);
            }
        }

        return true;
    }

    /**
     * Internal add processing
     */
    protected function _add()
    {
        $isAjax = $this->isAjaxRequest();
        $validator_name = $this->module_name . '.add';
        $this->val = Load::validator($validator_name);

        if ($this->val->check()) {
            if ($isAjax) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => implode(', ', $this->val->hatalar),
                    'errors' => $this->val->hatalar
                ]);
            }
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $model = Load::model($this->model_name);

            // Process fields
            $this->processFieldData($model);

            // Handle image upload
            if (!$this->handleImageUpload($model)) {
                return false;
            }

            // Execute pre-save callback
            $model = $this->executeBeforeSaveCallback($model);
            if ($model === false) {
                if ($isAjax) {
                    $this->jsonResponse([
                        'status' => 'error',
                        'message' => 'Operation cancelled by callback'
                    ]);
                }
                return false;
            }

            // Save model
            if ($model->save(true)) {
                // Execute post-save callback
                $this->executeAfterSaveCallback($model);

                if ($isAjax) {
                    $this->jsonResponse([
                        'status' => 'success',
                        'message' => $this->lang[$this->module_name]['message']['oke']['add'],
                        'redirect' => $this->site['saddress'] . $this->module_name . '/list'
                    ]);
                }
                $this->_oke_message($this->lang[$this->module_name]['message']['oke']['add']);
            } else {
                if ($isAjax) {
                    $this->jsonResponse([
                        'status' => 'error',
                        'message' => $this->lang[$this->module_name]['message']['error']['add']
                    ]);
                }
                $this->_error_message($this->lang[$this->module_name]['message']['error']['add']);
            }
        }
    }

    /**
     * Internal edit processing
     */
    protected function _edit($id = null)
    {
        $isAjax = $this->isAjaxRequest();
        $validator_name = $this->module_name . '.edit';
        $this->val = Load::validator($validator_name);

        if ($this->val->check($id)) {
            if ($isAjax) {
                $this->jsonResponse([
                    'status' => 'error',
                    'message' => implode(', ', $this->val->hatalar),
                    'errors' => $this->val->hatalar
                ]);
            }
            foreach ($_POST as $key => $val) {
                $this->tema->veri($key, Req::Post_Safe($key));
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $model = Load::model($this->model_name);
            $model->id = $id;

            // Process fields
            $this->processFieldData($model);

            // Remove user_id if it exists (don't update user_id on edit)
            if (isset($model->user_id)) {
                unset($model->user_id);
            }

            // Handle image upload
            if (!$this->handleImageUpload($model)) {
                return false;
            }

            // Execute pre-update callback
            $model = $this->executeBeforeUpdateCallback($model, $id);
            if ($model === false) {
                if ($isAjax) {
                    $this->jsonResponse([
                        'status' => 'error',
                        'message' => 'Operation cancelled by callback'
                    ]);
                }
                return false;
            }

            // Update model
            if ($model->update('id')) {
                // Execute post-update callback
                $this->executeAfterUpdateCallback($model, $id);

                if ($isAjax) {
                    $this->jsonResponse([
                        'status' => 'success',
                        'message' => $this->lang[$this->module_name]['message']['oke']['edit'],
                        'redirect' => $this->site['saddress'] . $this->module_name . '/list'
                    ]);
                }
                $this->_oke_message($this->lang[$this->module_name]['message']['oke']['edit']);
            } else {
                if ($isAjax) {
                    $this->jsonResponse([
                        'status' => 'error',
                        'message' => $this->lang[$this->module_name]['message']['error']['edit']
                    ]);
                }
                $this->_error_message($this->lang[$this->module_name]['message']['error']['edit']);
            }
        }
    }

    /**
     * Internal delete processing
     */
    protected function _delete($delete_ids)
    {
        $model = Load::model($this->model_name);

        foreach ($delete_ids as $s) {
            $model->flush();
            $model->where_id($s)->gRo();

            if ($model->getSize() === 0) {
                exit($this->lang[$this->module_name]['errors']['record']);
            }

            $model->sync();

            // Check moderation permission
            if ($this->user->checkPermission($this->module_name, 'moderate_delete', $model->user_id, $this->lang[$this->module_name]['role_errors']['moderate_delete']) === false) {
                return false;
            }

            // Execute pre-delete callback
            $callback_result = $this->executeBeforeDeleteCallback($model, $s);
            if ($callback_result === false) {
                return false; // Allow callback to cancel the operation
            }

            // Remove related gallery items
            if ($this->has_gallery) {
                $this->removeRelatedGallery($model->id);
            }

            // Remove related files
            if ($this->has_files) {
                $this->removeRelatedFiles($model->id);
            }

            // Remove images if they exist
            if (!empty($this->image_fields)) {
                foreach ($this->image_fields as $field_config) {
                    $field_name = $field_config['name'];
                    if ($model->$field_name != '') {
                        // Use image module if sizes are provided, otherwise use ImageService
                        if (isset($field_config['sizes'])) {
                            // Use image module for thumbnail deletion
                            $image = Load::mod('image');
                            $image->mod = $this->module_name;
                            $image->name = $field_name;
                            $image->url = $model->$field_name;
                            $image->_delete_files($this->getImageOptions($field_name));
                        } else {
                            // Use ImageService for standard deletion
                            $imageService = Load::lib('ImageService');
                            $imageService->deleteImage($this->module_name, $model->$field_name, $this->getImageOptions($field_name));
                        }
                    }
                }
            }

            // Delete the record
            $model->delete();

            // Execute post-delete callback
            $this->executeAfterDeleteCallback($s);
        }

        return true;
    }

    /**
     * Remove related gallery items
     */
    protected function removeRelatedGallery($record_id)
    {
        $gallery_mod = Load::model('mod_gallery_mod');
        if ($gallery_mod->exists_mod_name($this->module_name) === true) {
            Run::import('mod_gallery', '_remove', [$this->module_name, $record_id]);
        }
    }

    /**
     * Remove related files
     */
    protected function removeRelatedFiles($record_id)
    {
        $file_mod = Load::model('mod_attachment_mod');
        if ($file_mod->exists_mod_name($this->module_name) === true) {
            Run::import('mod_attachment', '_remove', [$this->module_name, $record_id]);
        }
    }

    /**
     * DataTable JSON generation - will use DataTableService
     */
    public function datatable_json()
    {
        $dataTableService = Load::lib('DataTableService');
        echo $dataTableService->generateJson(
            $this->module_name,
            $this->getDatatableColumns(),
            $this->getDatatableOptions()
        );
    }

    /**
     * Define datatable columns - override in child classes
     * 
     * Supports formatter API like menu URL patterns:
     * - "id" (simple column)
     * - "id|checkbox" (column with checkbox formatter)
     * - "image|image" (image column with thumbnail)
     * - "status|label" (status label with automatic formatting)
     * - "name|bold" (bold text)
     * - "created_at|date" (formatted date)
     * - "fiyat|currency" (formatted currency)
     * 
     * Available formatters:
     * - checkbox: Checkbox input
     * - image: Image thumbnail
     * - label: Status label (auto-detects success/warning/danger states)
     * - bold: Bold text
     * - italic: Italic text
     * - date: Date formatting (d.m.Y)
     * - datetime: Date and time formatting (d.m.Y H:i)
     * - time: Time formatting (H:i)
     * - number: Number formatting with thousands separator
     * - currency: Currency formatting (₺)
     * - truncate: Truncated text (50 chars)
     * - link: Clickable link
     * - email: Mailto link
     * - badge: Bootstrap badge
     */
    protected function getDatatableColumns()
    {
        return ['id'];
    }

    /**
     * Define datatable options - override in child classes
     */
    protected function getDatatableOptions()
    {
        return [];
    }

    // Common message methods
    protected function isAjaxRequest()
    {
        return (
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_POST['ajax_request']) && ($_POST['ajax_request'] === 'true' || $_POST['ajax_request'] === '1'))
        );
    }

    protected function jsonResponse(array $payload)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }

    protected function echoSuccessResponse()
    {
        if (in_array($this->module_name, ['mod_news', 'mod_content', 'mod_publication'])) {
            echo 'Success';
        } elseif (in_array($this->module_name, ['mod_video'])) {
            echo 'Oke';
        } else {
            echo 'OK';
        }
    }

    protected function _oke_message($message)
    {
        $this->tema->veri('ok', true);
        $this->tema->veri('message', $message);
    }

    protected function _error_message($message)
    {
        $this->tema->veri('error', true);
        $this->tema->veri('errors', [$message]);
    }

    protected function _error($message)
    {
        $this->tema->veri('error', true);
        $this->tema->veri('message', $message);
    }

    /**
     * Date validation helper
     */
    public function _valid_date($data = '')
    {
        if ($data == '') {
            return false;
        }
        return preg_match('@(\d{4})-(\d{2})-(\d{2})$@', $data);
    }

    /**
     * Generic single-column update method
     * Eliminates duplicate publish/unpublish and similar functions
     * 
     * @param string $column Column name to update
     * @param mixed $value New value for the column
     * @param array $ids Array of record IDs to update
     * @param string $permission_action Permission to check (usually 'edit')
     * @return bool Success status
     */
    protected function updateSingleColumn($column, $value, $ids, $permission_action = 'edit')
    {
        // Check permission
        if ($this->user->hasPermission($this->module_name, $permission_action, $this->lang[$this->module_name]['role_errors'][$permission_action]) === false) {
            return false;
        }

        $model = Load::model($this->model_name);

        if (is_array($ids)) {
            foreach ($ids as $id) {
                $model->id = $id;
                $model->$column = $value;
                if ($model->update('id')) {
                    continue;
                }
                return false;
            }
            return true;
        }

        return false;
    }

    /**
     * Publish records (set status = 'y')
     */
    public function publish()
    {
        $query = Req::Post_Safe('query');
        if ($this->updateSingleColumn('status', 'y', $query, 'publish')) {
            $this->echoSuccessResponse();
        } else {
            echo 'ERROR';
        }
    }

    /**
     * Unpublish records (set status = 'n')  
     */
    public function unpublish()
    {
        $query = Req::Post_Safe('query');
        if ($this->updateSingleColumn('status', 'n', $query, 'unpublish')) {
            $this->echoSuccessResponse();
        } else {
            echo 'ERROR';
        }
    }

    /**
     * Activate records (set durum = 'a')
     */
    public function activate()
    {
        $query = Req::Post_Safe('query');
        if ($this->updateSingleColumn('status', 'y', $query)) {
            $this->echoSuccessResponse();
        } else {
            echo 'ERROR';
        }
    }

    /**
     * Deactivate records (set durum = 'p')
     */
    public function deactivate()
    {
        $query = Req::Post_Safe('query');
        if ($this->updateSingleColumn('status', 'n', $query)) {
            $this->echoSuccessResponse();
        } else {
            echo 'ERROR';
        }
    }

    /**
     * Enable deck (set deck = 'y')
     */
    public function enableDeck()
    {
        $ids = Req::Post_Safe('query');
        if ($this->updateSingleColumn('deck', 'y', $ids)) {
            $this->echoSuccessResponse();
        } else {
            echo 'ERROR';
        }
    }

    /**
     * Disable deck (set deck = 'n')
     */
    public function disableDeck()
    {
        $ids = Req::Post_Safe('query');
        if ($this->updateSingleColumn('deck', 'n', $ids)) {
            $this->echoSuccessResponse();
        } else {
            echo 'ERROR';
        }
    }

    /**
     * Generic method for custom single-column updates
     * Can be used for any column/value combination
     */
    protected function handleSingleColumnUpdate($column, $value)
    {
        $query = Req::Post_Safe('query');
        if ($this->updateSingleColumn($column, $value, $query)) {
            $this->echoSuccessResponse();
        } else {
            echo 'ERROR';
        }
    }

    /**
     * Execute callback before save operation
     * Calls beforeSave() method which can be overridden in child classes
     */
    protected function executeBeforeSaveCallback($model)
    {
        $result = $this->beforeSave($model);
        if ($result === false) {
            return false; // Allow callback to cancel the operation
        }
        // If callback returns a model, use it; otherwise keep existing model
        if (is_object($result)) {
            $model = $result;
        }
        return $model; // Return the modified model
    }

    /**
     * Execute callback after successful save (add operation)
     * Calls afterSave() method which can be overridden in child classes
     */
    protected function executeAfterSaveCallback($model)
    {
        // Handle menu integration if pattern is configured and menu is selected
        if ($this->menu_url_pattern && Req::Post_Safe('menu') != 0) {
            $this->handleMenuIntegration($model, 'add');
        }

        // Execute custom callback
        $result = $this->afterSave($model);
        // If callback returns a model, use it; otherwise keep existing model
        if (is_object($result)) {
            $model = $result;
        }
        
        return $model;
    }

    /**
     * Execute callback before update operation
     * Calls beforeUpdate() method which can be overridden in child classes
     */
    protected function executeBeforeUpdateCallback($model, $id)
    {
        $result = $this->beforeUpdate($model, $id);
        if ($result === false) {
            return false; // Allow callback to cancel the operation
        }
        // If callback returns a model, use it; otherwise keep existing model
        if (is_object($result)) {
            $model = $result;
        }
        return $model; // Return the modified model
    }

    /**
     * Execute callback after successful update (edit operation)
     * Calls afterUpdate() method which can be overridden in child classes
     */
    protected function executeAfterUpdateCallback($model, $id)
    {
        // Handle menu integration if pattern is configured and menu is selected
        if ($this->menu_url_pattern && Req::Post_Safe('menu') != 0) {
            $this->handleMenuIntegration($model, 'update', $id);
        }

        // Execute custom callback
        $result = $this->afterUpdate($model, $id);
        // If callback returns a model, use it; otherwise keep existing model
        if (is_object($result)) {
            $model = $result;
        }
        
        return $model;
    }

    /**
     * Execute callback before delete operation
     * Calls beforeDelete() method which can be overridden in child classes
     */
    protected function executeBeforeDeleteCallback($model, $id)
    {
        $result = $this->beforeDelete($model, $id);
        if ($result === false) {
            return false; // Allow callback to cancel the operation
        }
        // If callback returns a model, use it; otherwise keep existing model
        if (is_object($result)) {
            $model = $result;
        }
        return $model; // Return the modified model
    }

    /**
     * Execute callback after successful delete operation
     * Calls afterDelete() method which can be overridden in child classes
     */
    protected function executeAfterDeleteCallback($id)
    {
        $result = $this->afterDelete($id);
        return $result;
    }

    /**
     * Generic menu integration handler with automatic ACL support
     */
    protected function handleMenuIntegration($model, $operation, $original_id = null)
    {
        if ($operation === 'add') {
            // Add new menu entry
            $menu_model = Load::model('mod_menu');
            $menu_model->parent_id = Req::Post_Safe('menu');
            $menu_model->name = $model->name;
            $menu_model->lang = $model->lang;
            $menu_model->status = $model->status;
            $menu_model->url = $this->generateMenuUrl($model);

            // Automatically include acl_id if model has it
            if (isset($model->acl_id)) {
                $menu_model->acl_id = $model->acl_id;
            }

            $menu_model->save();
        } elseif ($operation === 'update' && $original_id) {
            // Update existing menu entry if it exists
            $original_model = Load::model($this->model_name);
            $original_model->where_id($original_id)->gRo()->sync();
            $old_url = $this->generateMenuUrl($original_model);

            $mod_menu_model = Load::model('mod_menu');
            if ($mod_menu_model->exists_url($old_url)) {
                $mod_menu_model->getResultOne()->sync();
                $mod_menu_model->name = $model->name;
                $mod_menu_model->lang = $model->lang;
                $mod_menu_model->status = $model->status;
                $mod_menu_model->url = $this->generateMenuUrl($model);

                // Automatically include acl_id if model has it
                if (isset($model->acl_id)) {
                    $mod_menu_model->acl_id = $model->acl_id;
                }

                $mod_menu_model->update();
            }
        }
    }

    /**
     * Generate menu URL based on pattern
     * Override this method for custom URL patterns
     * 
     * Available placeholders:
     * - {lang} - model language
     * - {id} - model ID
     * - {field_name} - raw field value (e.g., {name}, {title}, {username})
     * - {field_name|sefurl} - SEO URL from field (e.g., {name|sefurl}, {title|sefurl})
     * - {field_name|upper} - uppercase field (e.g., {name|upper})
     * - {field_name|lower} - lowercase field (e.g., {title|lower})
     */
    protected function generateMenuUrl($model)
    {
        if ($this->menu_url_pattern) {
            // Use dynamic placeholder replacement for all fields including lang and id
            return $this->replaceDynamicPlaceholders($this->menu_url_pattern, $model);
        }
    }

    /**
     * Replace dynamic field placeholders in URL pattern
     * Supports patterns like {field_name} and {field_name|transformation}
     */
    protected function replaceDynamicPlaceholders($url, $model)
    {
        // Match patterns like {field_name} or {field_name|transformation}
        $pattern = '/\{([a-zA-Z_]+)(\|([a-zA-Z]+))?\}/';

        return preg_replace_callback($pattern, function ($matches) use ($model) {
            $field_name = $matches[1];
            $transformation = isset($matches[3]) ? $matches[3] : null;

            // Get field value (prefer POST data, fallback to model)
            $field_value = Req::Post($field_name) ?: (isset($model->$field_name) ? $model->$field_name : '');

            // Apply transformation if specified
            if ($transformation) {
                switch ($transformation) {
                    case 'sefurl':
                        return Helper::sefurl($field_value);
                    case 'upper':
                        return strtoupper($field_value);
                    case 'lower':
                        return strtolower($field_value);
                    default:
                        return $field_value;
                }
            }

            return $field_value;
        }, $url);
    }
}
