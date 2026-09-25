<?php
/**
 * Amed Framework
 * mod_content_category Index
 */

class mod_content_category_mod extends indexMain
{
    public $db;
    public $user;
    public $view;
    public $lang;
    public $session;

    public function __construct()
    {
        parent::__construct();
        $this->db = Load::lib('Database');
        $this->user = Load::model('mod_user.user');
        $this->view = Load::lib('AdminTemplate');
        $this->session = Load::lib('Session');
        $this->lang = Load::lang('mod_content_category');
        
        $this->view->lang($this->lang);
    }

    public function index()
    {
        $this->list();
    }

    private function getVisibleLanguages($currentRecordLang = '')
    {
        $langWhere = "WHERE status='y'";
        if ($currentRecordLang !== '') {
            $langWhere .= " OR seo='" . Req::Clear($currentRecordLang) . "'";
        }

        $this->db->fetch_array("SELECT * FROM `lang` " . $langWhere . " ORDER BY is_default DESC, seo ASC");
        $rows = $this->db->getResult()->toArray();

        return is_array($rows) ? $rows : [];
    }

    private function getVisibleLanguageCodes($currentRecordLang = '')
    {
        $codes = [];
        foreach ($this->getVisibleLanguages($currentRecordLang) as $row) {
            if (!empty($row['seo'])) {
                $codes[] = (string) $row['seo'];
            }
        }

        return $codes;
    }

    public function list()
    {
        if ($this->user->hasPermission('mod_content_category', 'list', $this->lang['mod_content_category']['role_errors']['list'], 1) === False) {
            return False;
        }

        Load::model('mod_content_category');
        $mod_content_category_model = new mod_content_category_model();
        $res = $mod_content_category_model->order('`order`', 'ASC')->gR()->toArray();

        $visibleLangs = $this->getVisibleLanguageCodes();
        $filtered = [];
        if (is_array($res)) {
            foreach ($res as $entry) {
                if (!isset($entry['lang']) || in_array((string) $entry['lang'], $visibleLangs, true)) {
                    $filtered[] = $entry;
                }
            }
        }

        Run::import('management', '_index');
        $this->view->set('all_categories', $filtered);
        $this->view->tpl('admin/mod_content_category/list');
    }

    private function imageOptions()
    {
        return [
            '/' => ['width' => 200, 'height' => null, 'option' => 'width'],
            '/thumb' => ['width' => 100, 'height' => null, 'option' => 'width'],
        ];
    }

    private function uploadImageOrFail($fieldName = 'image')
    {
        if (!isset($_FILES[$fieldName]['name']) || $_FILES[$fieldName]['name'] === '') {
            return null;
        }

        $image = Load::mod('image');
        $image->mod = 'mod_content_category';
        $image->name = $fieldName;

        if ($image->_resize($this->imageOptions())) {
            return $image->url;
        }

        $this->view->set('error', true);
        $this->view->set('message', $image->error);

        return false;
    }

    private function rollbackUploadedImage($imageUrl)
    {
        if (empty($imageUrl)) {
            return;
        }

        $image = Load::mod('image');
        $image->mod = 'mod_content_category';
        $image->url = $imageUrl;
        $image->_delete_files($this->imageOptions());
    }

    private function categoryDataFromPost(array $base = [])
    {
        $base['name'] = Req::Post_Safe('name');
        $base['lang'] = Req::Post_Safe('lang');
        $base['parent_id'] = intval(Req::Post_Safe('parent_id'));
        $base['root'] = Req::Post_Safe('root');
        $base['seo'] = Req::Post_Safe('seo');
        $base['title'] = Req::Post_Safe('title');
        $base['keywords'] = Req::Post_Safe('keywords');
        $base['description'] = Req::Post('description');
        $base['menu'] = intval(Req::Post_Safe('menu'));
        $base['acl_id'] = intval(Req::Post_Safe('acl_id'));

        return $base;
    }

    private function loadFormMetadata($currentRecordLang = '')
    {
        $visibleLangs = $this->getVisibleLanguages($currentRecordLang);
        $visibleCodes = $this->getVisibleLanguageCodes($currentRecordLang);
        $this->view->set('lang_lang', $visibleLangs);

        $cat_raw = $this->db->fetch_array("SELECT id, name, parent_id, lang FROM mod_content_category ORDER BY `order` ASC")->getResult()->toArray();
        $filteredCat = [];
        if (is_array($cat_raw)) {
            foreach ($cat_raw as $cRow) {
                if (!isset($cRow['lang']) || in_array((string) $cRow['lang'], $visibleCodes, true)) {
                    $filteredCat[] = $cRow;
                }
            }
        }
        $nested = Helper::nested($filteredCat, 'parent_id', 'id');
        $this->view->set('select_nested_parent_id_result', $nested);

        $mod_menu_raw = $this->db->fetch_array("SELECT id, name, parent_id, lang FROM mod_menu ORDER BY `order` ASC")->getResult()->toArray();
        $filteredMenu = [];
        if (is_array($mod_menu_raw)) {
            foreach ($mod_menu_raw as $mRow) {
                if (!isset($mRow['lang']) || in_array((string) $mRow['lang'], $visibleCodes, true)) {
                    $filteredMenu[] = $mRow;
                }
            }
        }
        $this->view->set('select_nested_menu_result', Helper::nested($filteredMenu, 'parent_id', 'id'));

        $this->db->fetch_array("SELECT id, name FROM mod_acl ORDER BY name ASC");
        $this->view->set('mod_acl_acl_id', $this->db->getResult()->toArray());
    }

    private function syncMenuOnCreate($category_model)
    {
        if ((int) Req::Post_Safe('menu') === 0) {
            return;
        }

        $menu_model = Load::model('mod_menu');
        $menu_model->parent_id = Req::Post_Safe('menu');
        $menu_model->name = $category_model->name;
        $menu_model->lang = $category_model->lang;
        $menu_model->url = $category_model->lang . '/kat/' . $category_model->seo;
        $menu_model->acl_id = $category_model->acl_id;
        $menu_model->save();
    }

    private function syncMenuOnUpdate($previous_url, $category_model)
    {
        $menu_model = Load::model('mod_menu');
        if ($menu_model->exists_url($previous_url)) {
            $menu_model->getResultOne()->sync();
            $menu_model->name = $category_model->name;
            $menu_model->lang = $category_model->lang;
            $menu_model->url = $category_model->lang . '/kat/' . $category_model->seo;
            $menu_model->acl_id = $category_model->acl_id;
            if (Req::is_Post('menu')) {
                $menu_model->parent_id = Req::Post_Safe('menu');
            }
            $menu_model->update();
            return;
        }

        if ((int) Req::Post_Safe('menu') !== 0) {
            $this->syncMenuOnCreate($category_model);
        }
    }

    private function deleteCategoryTree($id)
    {
        Load::model('mod_content_category');
        $mod_content_category_model = new mod_content_category_model();
        $mod_content_category_model->getid($id)->getResultOne(TRUE, TRUE);
        if ($mod_content_category_model->getSize() === 0) {
            print($this->lang['mod_content_category']['errors']['record']);
            return FALSE;
        }
        $mod_content_category_model->sync();

        if ($this->user->checkPermission('mod_content_category', 'moderate_delete', $mod_content_category_model->user_id, $this->lang['mod_content_category']['role_errors']['moderate_delete']) === False) {
            return FALSE;
        }

        $children_model = new mod_content_category_model();
        if ($children_model->exists_parent_id($id)) {
            foreach ($children_model->toArray() as $child) {
                if ($this->deleteCategoryTree($child['id']) !== TRUE) {
                    return FALSE;
                }
            }
        }

        if ($mod_content_category_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_content_category';
            $image->url = $mod_content_category_model->image;
            $image->_delete_files($this->imageOptions());
        }

        $gallery_mod = Load::model('mod_gallery_mod');
        if ($gallery_mod->exists_mod_name('mod_content_category') === TRUE) {
            Run::import('mod_gallery', '_remove', ['mod_content_category', $mod_content_category_model->id]);
        }

        $attachment_mod = Load::model('mod_attachment_mod');
        if ($attachment_mod->exists_mod_name('mod_content_category') === TRUE) {
            Run::import('mod_attachment', '_remove', ['mod_content_category', $mod_content_category_model->id]);
        }

        $menu_url = $mod_content_category_model->lang . '/kat/' . $mod_content_category_model->seo;
        $menu_model = Load::model('mod_menu');
        if ($menu_model->exists_url($menu_url)) {
            $menu_model->getResultOne()->delete();
        }

        $mod_content_category_model->delete();
        return TRUE;
    }

    public function add()
    {
        if ($this->user->hasPermission('mod_content_category', 'add', $this->lang['mod_content_category']['role_errors']['add'], 1) === False) {
            return False;
        }

        if (Req::is_Post('add')) {
            $this->val = Load::validator('mod_content_category.add');
            if ($this->val->check()) {
                foreach ($_POST as $key => $val) {
                    $this->view->set($key, Req::Post_Safe($key));
                }
                $this->view->set('errors', $this->val->errors);
                $this->view->set('error', true);
                $this->view->set('message', $this->lang['mod_content_category']['messages']['error']['validation'] ?? 'Ji kerema xwe re qadên pêwîst rast dagirin!');
            } else {
                Load::model('mod_content_category');
                $mod_content_category_model = new mod_content_category_model();
                $formData = $this->categoryDataFromPost();

                $mod_content_category_model->name = $formData['name'];
                $mod_content_category_model->lang = $formData['lang'];
                $mod_content_category_model->parent_id = $formData['parent_id'];
                $mod_content_category_model->root = $formData['root'];
                $mod_content_category_model->seo = $formData['seo'];
                $mod_content_category_model->title = $formData['title'];
                $mod_content_category_model->keywords = $formData['keywords'];
                $mod_content_category_model->description = $formData['description'];
                $mod_content_category_model->menu = $formData['menu'];
                $mod_content_category_model->acl_id = $formData['acl_id'];
                $mod_content_category_model->user_id = $this->session->get('id');
                
                $orderQuery = $this->db->fetch_array("SELECT MAX(`order`) as max_order FROM mod_content_category WHERE parent_id = " . intval(Req::Post_Safe('parent_id')))->getResult()->toArray();
                $maxOrder = isset($orderQuery[0]['max_order']) ? (int)$orderQuery[0]['max_order'] : 0;
                $mod_content_category_model->order = $maxOrder + 1;

                $uploadedImage = $this->uploadImageOrFail('image');
                if ($uploadedImage === false) {
                    foreach ($formData as $key => $value) {
                        $this->view->set($key, $value);
                    }
                } else {
                    if ($uploadedImage !== null) {
                        $mod_content_category_model->image = $uploadedImage;
                    }

                    if ($mod_content_category_model->save()) {
                        $this->syncMenuOnCreate($mod_content_category_model);
                        $this->view->set('Success', true);
                        $this->view->set('message', $this->lang['mod_content_category']['messages']['success']['added']);
                    } else {
                        $this->rollbackUploadedImage($uploadedImage);
                        foreach ($formData as $key => $value) {
                            $this->view->set($key, $value);
                        }
                        $this->view->set('error', true);
                        $this->view->set('message', $this->lang['mod_content_category']['messages']['error']['not_added']);
                    }
                }
            }
        }

        $this->loadFormMetadata();
        Run::import('management', '_index');
        $this->view->tpl('admin/mod_content_category/add');
    }

    public function edit($id)
    {
        if ($this->user->hasPermission('mod_content_category', 'edit', $this->lang['mod_content_category']['role_errors']['edit'], 1) === False) {
            return False;
        }

        Load::model('mod_content_category');
        $mod_content_category_model = new mod_content_category_model();
        $id = Req::Url_Clear($id);
        if ($mod_content_category_model->exists_id($id) === FALSE) {
            $this->view->set('error', true);
            $this->view->set('message', $this->lang['mod_content_category']['errors']['record']);
            $this->loadFormMetadata();
            Run::import('management', '_index');
            $this->view->tpl('admin/mod_content_category/edit');
            return;
        }

        $existing = $mod_content_category_model->getResultOne()->toArray();
        $viewData = $existing;
        $reloadFromDb = true;
        if ($this->user->checkPermission('mod_content_category', 'moderate_edit', $existing['user_id'], $this->lang['mod_content_category']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->val = Load::validator('mod_content_category.edit');
            if ($this->val->check($id)) {
                $this->view->set('errors', $this->val->errors);
                $this->view->set('error', true);
                $this->view->set('message', $this->lang['mod_content_category']['messages']['error']['validation'] ?? 'Ji kerema xwe re qadên pêwîst rast dagirin!');
                $viewData = $this->categoryDataFromPost($existing);
                $reloadFromDb = false;
            } else {
                $mod_content_category_model = new mod_content_category_model();
                $mod_content_category_model->id = $id;
                $formData = $this->categoryDataFromPost(['id' => $id]);

                $mod_content_category_model->name = $formData['name'];
                $mod_content_category_model->lang = $formData['lang'];
                $mod_content_category_model->parent_id = $formData['parent_id'];
                $mod_content_category_model->root = $formData['root'];
                $mod_content_category_model->seo = $formData['seo'];
                $mod_content_category_model->title = $formData['title'];
                $mod_content_category_model->keywords = $formData['keywords'];
                $mod_content_category_model->description = $formData['description'];
                $mod_content_category_model->menu = $formData['menu'];
                $mod_content_category_model->acl_id = $formData['acl_id'];

                $uploadedImage = $this->uploadImageOrFail('image');
                if ($uploadedImage === false) {
                    $viewData = array_merge($existing, $formData);
                    $reloadFromDb = false;
                } else {
                    if ($uploadedImage !== null) {
                        $mod_content_category_model->image = $uploadedImage;
                    }

                    if ($mod_content_category_model->update('id')) {
                        $this->syncMenuOnUpdate($existing['lang'] . '/kat/' . $existing['seo'], $mod_content_category_model);
                        $this->view->set('Success', true);
                        $this->view->set('message', $this->lang['mod_content_category']['messages']['success']['updated']);
                    } else {
                        $this->rollbackUploadedImage($uploadedImage);
                        $this->view->set('error', true);
                        $this->view->set('message', $this->lang['mod_content_category']['messages']['error']['not_updated']);
                    }
                }
            }
        }

        $res = $reloadFromDb ? $mod_content_category_model->where_id($id)->gRo()->toArray() : $viewData;
        $this->loadFormMetadata($existing['lang'] ?? '');

        Run::import('management', '_index');
        $this->view->set('d', $res);
        $this->view->tpl('admin/mod_content_category/edit');
    }

    public function image_remove_image()
    {
        if ($this->user->hasPermission('mod_content_category', 'edit', $this->lang['mod_content_category']['role_errors']['edit'], 1) === False) {
            return FALSE;
        }

        $id = intval(Req::Post_Safe('id'));
        Load::model('mod_content_category');
        $mod_content_category_model = new mod_content_category_model();
        $mod_content_category_model->where_id($id)->gRo();
        if ($mod_content_category_model->getSize() === 0) {
            echo $this->lang['mod_content_category']['errors']['record'];
            return FALSE;
        }

        $mod_content_category_model->sync();
        if ($this->user->checkPermission('mod_content_category', 'moderate_edit', $mod_content_category_model->user_id, $this->lang['mod_content_category']['role_errors']['moderate_edit']) === False) {
            echo $this->lang['mod_content_category']['role_errors']['moderate_edit'];
            return FALSE;
        }

        if ($mod_content_category_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_content_category';
            $image->url = $mod_content_category_model->image;
            if ($image->_delete_files($this->imageOptions())) {
                $mod_content_category_model->image = '';
                $mod_content_category_model->update('id');
                echo 'Success';
            } else {
                echo $image->error;
                return FALSE;
            }
        } else {
            echo 'Success';
        }
    }

    public function delete()
    {
        if ($this->user->hasPermission('mod_content_category', 'delete', $this->lang['mod_content_category']['role_errors']['delete']) === False) {
            return False;
        }

        $id_param = Req::Post_Safe('id');
        if ($id_param === NULL || $id_param === '') {
            $id_param = Req::Post_Safe('delete');
        }
        
        $ids = is_array($id_param) ? $id_param : [$id_param];
        
        foreach ($ids as $id) {
            $id = intval($id);
            if ($id > 0 && $this->deleteCategoryTree($id) !== TRUE) {
                echo 'ERROR';
                return FALSE;
            }
        }
        echo 'Success';
    }

    // Modern drag-drop delete endpoint compatibility
    public function dragdrop_delete()
    {
        $this->delete();
    }

    public function _order_change()
    {
        $order_array = Req::Post('order');
        if (empty($order_array) || !is_array($order_array)) {
            return TRUE;
        }

        $x = 1;
        foreach ($order_array as $category_id) {
            $id = intval($category_id);
            if ($id > 0) {
                $this->db->query("UPDATE `mod_content_category` SET `order` = '$x' WHERE `id` = '$id'");
            }
            $x++;
        }
        return TRUE;
    }

    public function reorder()
    {
        if ($this->user->hasPermission('mod_content_category', 'edit', $this->lang['mod_content_category']['role_errors']['edit']) === False) {
            return False;
        }

        $id = intval(Req::Post_Safe('id'));
        $parent_id = intval(Req::Post_Safe('parent_id'));

        if ($id > 0) {
            if ($this->db->query("UPDATE `mod_content_category` SET `parent_id` = '$parent_id' WHERE `id` = '$id'")) {
                if ($this->_order_change()) {
                    echo 'Success';
                } else {
                    echo 'OrderError';
                }
            } else {
                echo 'DBError';
            }
        } else {
            echo 'IDError';
        }
    }
}
