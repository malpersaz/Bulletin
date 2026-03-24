<?php
/**
 * Amed Framework
 * mod_category Index
 */

class mod_category_mod extends indexMain
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
        $this->lang = Load::lang('mod_category');
        
        $this->view->lang($this->lang);
    }

    public function index()
    {
        $this->list();
    }

    public function list()
    {
        if ($this->user->hasPermission('mod_category', 'list', $this->lang['mod_category']['role_errors']['list'], 1) === False) return False;

        Load::model('mod_category');
        $mod_category_model = new mod_category_model();
        $res = $mod_category_model->order('`order`', 'ASC')->gR()->toArray();

        Run::import('management', '_index');
        $this->view->veri('all_categories', $res);
        $this->view->tpl('admin/mod_category/list');
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
        $image->mod = 'mod_category';
        $image->name = $fieldName;

        if ($image->_resize($this->imageOptions())) {
            return $image->url;
        }

        $this->view->veri('error', true);
        $this->view->veri('mesaj', $image->error);

        return false;
    }

    private function rollbackUploadedImage($imageUrl)
    {
        if (empty($imageUrl)) {
            return;
        }

        $image = Load::mod('image');
        $image->mod = 'mod_category';
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

    private function loadFormMetadata()
    {
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY seo ASC");
        $this->view->veri('lang_lang', $this->db->getResult()->toArray());

        $cat_raw = $this->db->fetch_array("SELECT id, name, parent_id, lang FROM mod_category ORDER BY `order` ASC")->getResult()->toArray();
        $nested = Helper::nested($cat_raw, 'parent_id', 'id');
        $this->view->veri('select_nested_parent_id_result', $nested);

        $mod_menu_raw = $this->db->fetch_array("SELECT id, name, parent_id, lang FROM mod_menu ORDER BY `order` ASC")->getResult()->toArray();
        $this->view->veri('select_nested_menu_result', Helper::nested($mod_menu_raw, 'parent_id', 'id'));

        $this->db->fetch_array("SELECT id, name FROM mod_acl ORDER BY name ASC");
        $this->view->veri('mod_acl_acl_id', $this->db->getResult()->toArray());
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
        Load::model('mod_category');
        $mod_category_model = new mod_category_model();
        $mod_category_model->getid($id)->getResultOne(TRUE, TRUE);
        if ($mod_category_model->getSize() === 0) {
            print($this->lang['mod_category']['errors']['record']);
            return FALSE;
        }
        $mod_category_model->sync();

        if ($this->user->checkPermission('mod_category', 'moderate_delete', $mod_category_model->user_id, $this->lang['mod_category']['role_errors']['moderate_delete']) === False) {
            return FALSE;
        }

        $children_model = new mod_category_model();
        if ($children_model->exists_parent_id($id)) {
            foreach ($children_model->toArray() as $child) {
                if ($this->deleteCategoryTree($child['id']) !== TRUE) {
                    return FALSE;
                }
            }
        }

        if ($mod_category_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_category';
            $image->url = $mod_category_model->image;
            $image->_delete_files($this->imageOptions());
        }

        $gallery_mod = Load::model('mod_gallery_mod');
        if ($gallery_mod->exists_mod_name('mod_category') === TRUE) {
            Run::import('mod_gallery', '_remove', ['mod_category', $mod_category_model->id]);
        }

        $attachment_mod = Load::model('mod_attachment_mod');
        if ($attachment_mod->exists_mod_name('mod_category') === TRUE) {
            Run::import('mod_attachment', '_remove', ['mod_category', $mod_category_model->id]);
        }

        $menu_url = $mod_category_model->lang . '/kat/' . $mod_category_model->seo;
        $menu_model = Load::model('mod_menu');
        if ($menu_model->exists_url($menu_url)) {
            $menu_model->getResultOne()->delete();
        }

        $mod_category_model->delete();
        return TRUE;
    }

    public function add()
    {
        if ($this->user->hasPermission('mod_category', 'add', $this->lang['mod_category']['role_errors']['add'], 1) === False) return False;

        if (Req::is_Post('add')) {
            $this->val = Load::validator('mod_category.add');
            if ($this->val->check()) {
                foreach ($_POST as $key => $val) {
                    $this->view->veri($key, Req::Post_Safe($key));
                }
                $this->view->veri('hatalar', $this->val->hatalar);
                $this->view->veri('error', true);
                $this->view->veri('mesaj', $this->lang['mod_category']['messages']['error']['validation'] ?? 'Ji kerema xwe re qadên pêwîst rast dagirin!');
            } else {
                Load::model('mod_category');
                $mod_category_model = new mod_category_model();
                $formData = $this->categoryDataFromPost();

                $mod_category_model->name = $formData['name'];
                $mod_category_model->lang = $formData['lang'];
                $mod_category_model->parent_id = $formData['parent_id'];
                $mod_category_model->root = $formData['root'];
                $mod_category_model->seo = $formData['seo'];
                $mod_category_model->title = $formData['title'];
                $mod_category_model->keywords = $formData['keywords'];
                $mod_category_model->description = $formData['description'];
                $mod_category_model->menu = $formData['menu'];
                $mod_category_model->acl_id = $formData['acl_id'];
                $mod_category_model->user_id = $this->session->getir('id');
                
                $orderQuery = $this->db->fetch_array("SELECT MAX(`order`) as max_order FROM mod_category WHERE parent_id = " . intval(Req::Post_Safe('parent_id')))->getResult()->toArray();
                $maxOrder = isset($orderQuery[0]['max_order']) ? (int)$orderQuery[0]['max_order'] : 0;
                $mod_category_model->order = $maxOrder + 1;

                $uploadedImage = $this->uploadImageOrFail('image');
                if ($uploadedImage === false) {
                    foreach ($formData as $key => $value) {
                        $this->view->veri($key, $value);
                    }
                } else {
                    if ($uploadedImage !== null) {
                        $mod_category_model->image = $uploadedImage;
                    }

                    if ($mod_category_model->save()) {
                        $this->syncMenuOnCreate($mod_category_model);
                        $this->view->veri('oke', true);
                        $this->view->veri('mesaj', $this->lang['mod_category']['messages']['success']['added']);
                    } else {
                        $this->rollbackUploadedImage($uploadedImage);
                        foreach ($formData as $key => $value) {
                            $this->view->veri($key, $value);
                        }
                        $this->view->veri('error', true);
                        $this->view->veri('mesaj', $this->lang['mod_category']['messages']['error']['not_added']);
                    }
                }
            }
        }

        $this->loadFormMetadata();
        Run::import('management', '_index');
        $this->view->tpl('admin/mod_category/add');
    }

    public function edit($id)
    {
        if ($this->user->hasPermission('mod_category', 'edit', $this->lang['mod_category']['role_errors']['edit'], 1) === False) return False;

        Load::model('mod_category');
        $mod_category_model = new mod_category_model();
        $id = Req::Url_Clear($id);
        if ($mod_category_model->exists_id($id) === FALSE) {
            $this->view->veri('error', true);
            $this->view->veri('mesaj', $this->lang['mod_category']['errors']['record']);
            $this->loadFormMetadata();
            Run::import('management', '_index');
            $this->view->tpl('admin/mod_category/edit');
            return;
        }

        $existing = $mod_category_model->getResultOne()->toArray();
        $viewData = $existing;
        $reloadFromDb = true;
        if ($this->user->checkPermission('mod_category', 'moderate_edit', $existing['user_id'], $this->lang['mod_category']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->val = Load::validator('mod_category.edit');
            if ($this->val->check($id)) {
                $this->view->veri('hatalar', $this->val->hatalar);
                $this->view->veri('error', true);
                $this->view->veri('mesaj', $this->lang['mod_category']['messages']['error']['validation'] ?? 'Ji kerema xwe re qadên pêwîst rast dagirin!');
                $viewData = $this->categoryDataFromPost($existing);
                $reloadFromDb = false;
            } else {
                $mod_category_model = new mod_category_model();
                $mod_category_model->id = $id;
                $formData = $this->categoryDataFromPost(['id' => $id]);

                $mod_category_model->name = $formData['name'];
                $mod_category_model->lang = $formData['lang'];
                $mod_category_model->parent_id = $formData['parent_id'];
                $mod_category_model->root = $formData['root'];
                $mod_category_model->seo = $formData['seo'];
                $mod_category_model->title = $formData['title'];
                $mod_category_model->keywords = $formData['keywords'];
                $mod_category_model->description = $formData['description'];
                $mod_category_model->menu = $formData['menu'];
                $mod_category_model->acl_id = $formData['acl_id'];

                $uploadedImage = $this->uploadImageOrFail('image');
                if ($uploadedImage === false) {
                    $viewData = array_merge($existing, $formData);
                    $reloadFromDb = false;
                } else {
                    if ($uploadedImage !== null) {
                        $mod_category_model->image = $uploadedImage;
                    }

                    if ($mod_category_model->update('id')) {
                        $this->syncMenuOnUpdate($existing['lang'] . '/kat/' . $existing['seo'], $mod_category_model);
                        $this->view->veri('oke', true);
                        $this->view->veri('mesaj', $this->lang['mod_category']['messages']['success']['updated']);
                    } else {
                        $this->rollbackUploadedImage($uploadedImage);
                        $this->view->veri('error', true);
                        $this->view->veri('mesaj', $this->lang['mod_category']['messages']['error']['not_updated']);
                    }
                }
            }
        }

        $res = $reloadFromDb ? $mod_category_model->where_id($id)->gRo()->toArray() : $viewData;
        $this->loadFormMetadata();

        Run::import('management', '_index');
        $this->view->veri('d', $res);
        $this->view->tpl('admin/mod_category/edit');
    }

    public function image_remove_image()
    {
        if ($this->user->hasPermission('mod_category', 'edit', $this->lang['mod_category']['role_errors']['edit'], 1) === False) return FALSE;

        $id = intval(Req::Post_Safe('id'));
        Load::model('mod_category');
        $mod_category_model = new mod_category_model();
        $mod_category_model->where_id($id)->gRo();
        if ($mod_category_model->getSize() === 0) {
            echo $this->lang['mod_category']['errors']['record'];
            return FALSE;
        }

        $mod_category_model->sync();
        if ($this->user->checkPermission('mod_category', 'moderate_edit', $mod_category_model->user_id, $this->lang['mod_category']['role_errors']['moderate_edit']) === False) {
            echo $this->lang['mod_category']['role_errors']['moderate_edit'];
            return FALSE;
        }

        if ($mod_category_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_category';
            $image->url = $mod_category_model->image;
            if ($image->_delete_files($this->imageOptions())) {
                $mod_category_model->image = '';
                $mod_category_model->update('id');
                echo 'Oke';
            } else {
                echo $image->error;
                return FALSE;
            }
        } else {
            echo 'Oke';
        }
    }

    public function sil()
    {
        if ($this->user->hasPermission('mod_category',  'delete', $this->lang['mod_category']['role_errors'][ 'delete']) === False) return False;

        $id_param = Req::Post_Safe('id');
        if ($id_param === NULL || $id_param === '') {
            $id_param = Req::Post_Safe('delete');
        }
        if ($id_param === NULL || $id_param === '') {
            $id_param = Req::Post_Safe('sil');
        }
        
        $ids = is_array($id_param) ? $id_param : [$id_param];
        
        foreach ($ids as $id) {
            $id = intval($id);
            if ($id > 0) {
                if ($this->deleteCategoryTree($id) !== TRUE) {
                    echo 'ERROR';
                    return FALSE;
                }
            }
        }
        echo 'Oke';
    }

    // Modern drag-drop delete endpoint compatibility
    public function dragdrop_delete()
    {
        $this->sil();
    }

    public function _order_change()
    {
        $order_array = Req::Post('order');
        if (empty($order_array) || !is_array($order_array)) return TRUE;

        $x = 1;
        foreach ($order_array as $category_id) {
            $id = intval($category_id);
            if ($id > 0) {
                $this->db->query("UPDATE `mod_category` SET `order` = '$x' WHERE `id` = '$id'");
            }
            $x++;
        }
        return TRUE;
    }

    public function tasi()
    {
        if ($this->user->hasPermission('mod_category', 'edit', $this->lang['mod_category']['role_errors']['edit']) === False) return False;

        $id = intval(Req::Post_Safe('id'));
        $parent_id = intval(Req::Post_Safe('parent_id'));

        if ($id > 0) {
            if ($this->db->query("UPDATE `mod_category` SET `parent_id` = '$parent_id' WHERE `id` = '$id'")) {
                if ($this->_order_change()) {
                    echo 'Oke';
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
