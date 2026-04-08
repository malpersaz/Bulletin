<?php
/* AKM Software.
 * @2015
 * mod_page Modül Sayfası
 * mod/mod_page/index.php
 */
class mod_page_mod extends indexMain
{
    public $mod_page_model;

    function __construct()
    {
        parent::__construct();
        // Tema Kütüphanesini Çağıralım
        $this->tema = Load::lib('AdminTemplate');
        // Database Kütüphanesini Yükleyelim
        $this->db = Load::lib('Database');
        // Site ayarlarını çekelim
        //$this->site = Kayit::getir('site_config');
        // Dil Dosyasını Çağıralım
        $this->lang = Load::lang('mod_page');
        // Dil Dosyasını Temaya Gönderelim
        $this->tema->lang($this->lang);
        // Session Kütüphanesi
        $this->session = Load::lib('Session');
        // User Model
        $this->user = Load::model('mod_user.user');
    }

    private function resolveSelectedLang($fallback = '')
    {
        if (Req::is_Post('lang')) {
            return Req::Post_Safe('lang');
        }

        if (Req::is_Get('lang')) {
            return Req::Get_Safe('lang');
        }

        return $fallback;
    }

    private function getOptionalInt($name, $default = 0)
    {
        $value = Req::Post_Safe($name);
        if ($value === '' || !Req::isNumeric((string) $value)) {
            return $default;
        }

        return (int) $value;
    }

    private function loadFormLookups($selectedLang = '', $excludePageId = 0)
    {
        $this->db->fetch_array("SELECT * FROM `lang` ORDER BY seo DESC");
        $langs = $this->db->getResult()->toArray();
        if ($selectedLang === '') {
            $availableLangs = array_column($langs, 'seo');
            $siteLang = isset($this->site['lang']) ? $this->site['lang'] : '';

            if ($siteLang !== '' && in_array($siteLang, $availableLangs, TRUE)) {
                $selectedLang = $siteLang;
            } elseif (isset($langs[0]['seo'])) {
                $selectedLang = $langs[0]['seo'];
            }
        }

        $this->tema->veri('lang_lang', $langs);
        $this->tema->veri('selected_lang', $selectedLang);

        $mod_page_nested = Load::model('mod_page');
        $mod_page_nested->flush();
        if ($selectedLang !== '') {
            $mod_page_nested->where_lang($selectedLang);
        }
        $mod_page_nested->order('order', 'ASC')->all();
        $this->tema->veri('select_nested_parent_id_result', Helper::nested($mod_page_nested->toArray(), 'parent_id', 'id'));

        $this->db->fetch_array("SELECT * FROM `mod_acl` ORDER BY id DESC");
        $this->tema->veri('mod_acl_acl_id', $this->db->getResult()->toArray());

        $mod_news_category_nested = Load::model('mod_news_category');
        $mod_news_category_nested->flush();
        if ($selectedLang !== '') {
            $mod_news_category_nested->where_lang($selectedLang);
        }
        $mod_news_category_nested->order('order', 'ASC')->all();
        $this->tema->veri('select_nested_category_id_result', Helper::nested($mod_news_category_nested->toArray(), 'parent_id', 'id'));

        $mod_menu_nested = Load::model('mod_menu');
        $mod_menu_nested->flush();
        if ($selectedLang !== '') {
            $mod_menu_nested->where_lang($selectedLang);
        }
        $mod_menu_nested->order('order', 'ASC')->all();
        $this->tema->veri('select_nested_menu_result', Helper::nested($mod_menu_nested->toArray(), 'parent_id', 'id'));

        $pageQuery = "SELECT id, name, lang FROM `mod_page`";
        $where = [];
        if ($selectedLang !== '') {
            $where[] = "lang='" . $selectedLang . "'";
        }
        if ((int) $excludePageId !== 0) {
            $where[] = "id!='" . (int) $excludePageId . "'";
        }
        if (!empty($where)) {
            $pageQuery .= " WHERE " . implode(' AND ', $where);
        }
        $pageQuery .= " ORDER BY id DESC";
        $this->db->fetch_array($pageQuery);
        $pageReferences = $this->db->getResult()->toArray();
        $this->tema->veri('mod_page_gallery_id', $pageReferences);
        $this->tema->veri('mod_page_file_id', $pageReferences);
    }

    private function syncMenuRecord($menuParentId, $oldUrl = '')
    {
        if ($oldUrl !== '' && $menuParentId === 0) {
            $existingMenu = Load::model('mod_menu');
            if ($existingMenu->exists_url($oldUrl)) {
                $existingMenu->getResultOne()->sync();
                return $existingMenu->delete();
            }
        }

        if ($menuParentId === 0) {
            return TRUE;
        }

        $mod_menu_model = Load::model('mod_menu');
        $menuExists = FALSE;

        if ($oldUrl !== '' && $mod_menu_model->exists_url($oldUrl)) {
            $mod_menu_model->getResultOne()->sync();
            $menuExists = TRUE;
        }

        if (!$menuExists) {
            $mod_menu_model = Load::model('mod_menu');
            $mod_menu_model->user_id = $this->mod_page_model->user_id;
            $nextOrderQuery = "SELECT MAX(`order`) AS max_order FROM `mod_menu` WHERE parent_id='" . (int) $menuParentId . "'";
            if ($this->mod_page_model->lang !== '') {
                $nextOrderQuery .= " AND lang='" . $this->mod_page_model->lang . "'";
            }
            $this->db->fetch_array($nextOrderQuery);
            $orderRows = $this->db->getResult()->toArray();
            $orderResult = isset($orderRows[0]) ? $orderRows[0] : [];
            $maxOrder = isset($orderResult['max_order']) && Req::isNumeric((string) $orderResult['max_order']) ? (int) $orderResult['max_order'] : 0;
            $mod_menu_model->order = $maxOrder + 1;
        }

        $mod_menu_model->parent_id = $menuParentId;
        $mod_menu_model->name = $this->mod_page_model->name;
        $mod_menu_model->lang = $this->mod_page_model->lang;
        $mod_menu_model->status = $this->mod_page_model->status;
        $mod_menu_model->acl_id = $this->mod_page_model->acl_id;
        $mod_menu_model->url = $this->mod_page_model->lang . '/page/' . $this->mod_page_model->seo;

        if ($menuExists) {
            return $mod_menu_model->update();
        }

        return $mod_menu_model->save();
    }

    // Ekleme Sayfası
    public function add()
    {
        // Ekleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_page', 'add', $this->lang['mod_page']['role_errors']['add'], 1) === False) {
            return False;
        }
        if (Req::is_Post('add')) {
            $this->_add();
        }
        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_page']['add'];
        $this->tema->tpl('mod_page/add');
        $this->tema->veri('site', $this->site);
        $this->loadFormLookups($this->resolveSelectedLang());
    }

    // Düzenleme Sayfası
    public function edit($id = NULL)
    {
        // Düzenleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_page', 'edit', $this->lang['mod_page']['role_errors']['edit'], 1) === False) {
            return False;
        }
        $this->mod_page_model = Load::model('mod_page');
        $id = Req::Url_Clear($id);
        if ($this->mod_page_model->exists_id($id) === False) {
            $this->_error($this->lang['mod_page']['errors']['record']);
            Run::import('management', '_index');
            $this->tema->tpl('mod_page/edit');
            return;
        }

        $d = $this->mod_page_model->getResultOne()->toArray();

        // Eğer Eklenen İçerik Başkasınınsa Yönetme Permissionsi Varmı ?
        if ($this->user->checkPermission('mod_page', 'moderate_edit', $d['user_id'], $this->lang['mod_page']['role_errors']['moderate_edit'], 1) === False) {
            return False;
        }

        if (Req::is_Post('edit')) {
            $this->_edit($id);
            // Validation'dan dönen hatalar varsa $d içini $_POST ile güncelleyelim.
            if (isset($this->val) && $this->val->check($id)) {
                foreach ($_POST as $key => $val) {
                    if ($key == 'content') {
                        $d[$key] = Req::Post('content');
                    } else {
                        $d[$key] = Req::Post_Safe($key);
                    }
                }
            }
        }

        Run::import('management', '_index');

        $this->site['title'] = $this->lang['mod_page']['edit'];
        $this->tema->veri('site', $this->site);
        $this->tema->veri('d', $d);
        $this->tema->tpl('mod_page/edit');
        $this->loadFormLookups($this->resolveSelectedLang($d['lang']), (int) $d['id']);
    }

    // Listeleme Sayfası
    public function list()
    {
        // Listeleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_page', 'list', $this->lang['mod_page']['role_errors']['list'], 1) === False) {
            return False;
        }

        Run::import('management', '_index');
        $this->site['title'] = $this->lang['mod_page']['list'];
        $this->tema->tpl('mod_page/list');
        $this->tema->veri('site', $this->site);
        $mod_page_nested = Load::model('mod_page');
        $mod_page_nested->order('order', 'ASC')->all();
        $select_nested_mod_page_result = Helper::nested($mod_page_nested->toArray(), 'parent_id', 'id');
        $this->tema->veri('select_nested_mod_page_result', $select_nested_mod_page_result);
    }
    // mod_page Sıra Değişim
    public function _sira_degis()
    {
        $b = Req::Post_Safe('order');
        $mod_page_model = new mod_page_model();
        $x = 1;
        foreach ($b as $a) {
            $mod_page_model->id = $a;
            $mod_page_model->order = $x;
            if ($mod_page_model->update('id') === False) {
                //print_r($mod_page_model,TRUE);
                return FALSE;
            }
            $x++;
        }
        return TRUE;
    }


    // mod_page Taşıma
    public function tasi()
    {
        // Taşıma Permissionsi varmı?
        if ($this->user->hasPermission('mod_page', 'edit', $this->lang['mod_page']['role_errors']['edit']) === False) {
            return False;
        }
        $mod_page_model = Load::model('mod_page');
        $mod_page_model->id = Req::Post_Safe('id');
        $mod_page_model->parent_id = Req::Post_Safe('parent_id');
        if ($mod_page_model->update('id')  and $this->_sira_degis()) {
            echo 'Oke';
        } else {
            echo 'Error';
            //print_r($mod_page_model);
        }
    }
    public function image_remove_image()
    {
        // Düzenleme Permissionsi varmı?
        if ($this->user->hasPermission('mod_page', 'edit', $this->lang['mod_page']['role_errors']['edit'], 1) === False) {
            return FALSE;
        }

        $id = intval(Req::Post_Safe('id'));

        $this->mod_page_model = Load::model('mod_page');
        $this->mod_page_model->where_id($id)->gRo();
        if ($this->mod_page_model->getSize() === 0) {
            echo $this->lang['mod_page']['errors']['record'];
            return FALSE;
        }


        $this->mod_page_model->sync();
        // Eğer Eklenen İçerik Başkasınınsa Yönetme Permissionsi Varmı ?
        if ($this->user->checkPermission('mod_page', 'moderate_edit', $this->mod_page_model->user_id, $this->lang['mod_page']['role_errors']['moderate_edit']) === False) {
            echo $this->lang['mod_page']['role_errors']['moderate_edit'];
            return False;
        }

        if ($this->mod_page_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_page';
            $image->url = $this->mod_page_model->image;
            $option = [
                '/' =>
                [
                    'width' => 1200,
                    'height' => NULL,
                ],
                '/thumb' =>
                [
                    'width' => 250,
                    'height' => NULL,
                    'option' => 'width',
                ],
            ];
            try {
                if ($image->_delete_files($option)) {
                    $this->mod_page_model->image = '';
                    $this->mod_page_model->update('id');
                    echo 'Oke';
                } else {
                    echo $image->error;
                    return FALSE;
                }
            } catch (Throwable $e) {
                echo $e->getMessage();
                return FALSE;
            }
        } else {
            echo 'Oke';
        }
    }

    // Ekleme Fonksiyonu
    private function _add()
    {
        $postedWizardStep = $this->getOptionalInt('wizard_step', 1);
        $this->tema->veri('wizard_step', $postedWizardStep);

        $this->val = Load::validator('mod_page.add');
        if ($this->val->check()) {
            foreach ($_POST as $key => $val) {
                if ($key == 'content') {
                    $this->tema->veri($key, Req::Post('content'));
                } elseif ($key == 'lang') {
                    $this->tema->veri('selected_lang', Req::Post_Safe('lang'));
                } else {
                    $this->tema->veri($key, Req::Post_Safe($key));
                }
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_page_model = Load::model('mod_page');

            $this->mod_page_model->lang = Req::Post_Safe('lang');
            $this->mod_page_model->name = Req::Post_Safe('name');









            $this->mod_page_model->parent_id = $this->getOptionalInt('parent_id');
            $this->mod_page_model->root = Req::Post_Safe('root');
            $this->mod_page_model->status = Req::Post_Safe('status');

            $this->mod_page_model->deck = Req::Post_Safe('deck');

            $this->mod_page_model->summary = strip_tags(Req::Post('summary'));

            $this->mod_page_model->content = Req::Post('content');
            $this->mod_page_model->content = Helper::clear_html($this->mod_page_model->content);

            $this->mod_page_model->gallery_id = $this->getOptionalInt('gallery_id');
            $this->mod_page_model->file_id = $this->getOptionalInt('file_id');
            $this->mod_page_model->seo = Req::Post_Safe('seo');
            $this->mod_page_model->title = Req::Post_Safe('title');
            $this->mod_page_model->keywords = Req::Post_Safe('keywords');
            $this->mod_page_model->description = Req::Post_Safe('description');
            $this->mod_page_model->acl_id = $this->getOptionalInt('acl_id');
            $this->mod_page_model->category_id = $this->getOptionalInt('category_id');
            $this->mod_page_model->menu = $this->getOptionalInt('menu');
            $this->mod_page_model->user_id = $this->session->get('id');

            // Eğer Resim varsa
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_page';
                $image->name = 'image';
                $option = [
                    '/' =>
                    [
                        'width' => 1200,
                        'height' => NULL,
                    ],
                    '/thumb' =>
                    [
                        'width' => 250,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                ];
                if ($image->_resize($option)) {
                    $this->mod_page_model->image = $image->url;
                } else {
                    $this->_error_message($image->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_page_model->image)) {
                    unset($this->mod_page_model->image);
                }
            }

            if ($this->mod_page_model->save(TRUE)) {
                if ($this->syncMenuRecord($this->mod_page_model->menu) === FALSE) {
                    if (isset($image->url)) {
                        $image->_rollback();
                    }
                    $this->_error_message($this->lang['mod_page']['messages']['error']['add']);
                    return FALSE;
                }
                $this->_oke_message($this->lang['mod_page']['messages']['oke']['add']);
                Helper::yonlendir($this->site['sadres'] . 'mod_page/list', 1);
            } else {
                // Eğer Resim varsa
                if (isset($image->url)) {
                    $image->_rollback();
                }

                $this->_error_message($this->lang['mod_page']['messages']['error']['add']);
            }
        }
    }

    // Düzenleme Fonksiyonu
    private function _edit($id = NULL)
    {
        $this->val = Load::validator('mod_page.edit');
        if ($this->val->check($id)) {
            foreach ($_POST as $key => $val) {
                if ($key == 'content') {
                    $this->tema->veri($key, Req::Post('content'));
                } else {
                    $this->tema->veri($key, Req::Post_Safe($key));
                }
            }
            $this->tema->veri('hatalar', $this->val->hatalar);
        } else {
            $this->mod_page_model = Load::model('mod_page');

            $this->mod_page_model->id = $id;
            $this->mod_page_model->lang = Req::Post_Safe('lang');
            $this->mod_page_model->name = Req::Post_Safe('name');





            $this->mod_page_model->parent_id = $this->getOptionalInt('parent_id');
            $this->mod_page_model->root = Req::Post_Safe('root');
            $this->mod_page_model->status = Req::Post_Safe('status');

            $this->mod_page_model->deck = Req::Post_Safe('deck');

            $this->mod_page_model->summary = strip_tags(Req::Post('summary'));

            $this->mod_page_model->content = Req::Post('content');
            $this->mod_page_model->content = Helper::clear_html($this->mod_page_model->content);

            $this->mod_page_model->gallery_id = $this->getOptionalInt('gallery_id');
            $this->mod_page_model->file_id = $this->getOptionalInt('file_id');
            $this->mod_page_model->seo = Req::Post_Safe('seo');
            $this->mod_page_model->title = Req::Post_Safe('title');
            $this->mod_page_model->keywords = Req::Post_Safe('keywords');
            $this->mod_page_model->description = Req::Post_Safe('description');
            $this->mod_page_model->acl_id = $this->getOptionalInt('acl_id');
            $this->mod_page_model->category_id = $this->getOptionalInt('category_id');
            $this->mod_page_model->menu = $this->getOptionalInt('menu');
            // Bu İçerik Daha Önce Menüye Gönderilmişse Güncelleyelim
            $new_mod_page = new mod_page_model();
            $new_mod_page->where_id($id)->gRo()->sync();
            $url = $new_mod_page->lang . '/page/' . $new_mod_page->seo;

            if (isset($this->mod_page_model->user_id)) {
                unset($this->mod_page_model->user_id);
            }

            // Eğer Resim Varsa
            if (isset($_FILES['image']['name']) &&  ($_FILES['image']['name'] != '')) {
                $image = Load::mod('image');
                $image->mod = 'mod_page';
                $image->name = 'image';
                $option = [
                    '/' =>
                    [
                        'width' => 1200,
                        'height' => NULL,
                    ],
                    '/thumb' =>
                    [
                        'width' => 250,
                        'height' => NULL,
                        'option' => 'width',
                    ],
                ];
                if ($image->_resize($option)) {
                    $this->mod_page_model->image = $image->url;
                } else {
                    $this->_error_message($image->error);
                    return FALSE;
                }
            } else {
                if (isset($this->mod_page_model->image)) {
                    unset($this->mod_page_model->image);
                }
            }

            $this->mod_page_model->id = $id;
            if ($this->mod_page_model->update('id')) {
                if ($this->syncMenuRecord($this->mod_page_model->menu, $url) === FALSE) {
                    if (isset($image->url)) {
                        $image->_rollback();
                    }
                    $this->_error_message($this->lang['mod_page']['messages']['error']['edit']);
                    return FALSE;
                }


                $this->_oke_message($this->lang['mod_page']['messages']['oke']['edit']);
                Helper::yonlendir($this->site['sadres'] . 'mod_page/list', 1);
            } else {
                // Eğer Resim varsa
                if (isset($image->url)) {
                    $image->_rollback();
                }
                $this->_error_message($this->lang['mod_page']['messages']['error']['edit']);
            }
        }
    }


    public function _valid_date($data = '')
    {
        if ($data == '') {
            return false;
        }
        return preg_match('@(\d{4})-(\d{2})-(\d{2})$@', $data);
    }


    // Silme Sayfası
    public function dragdrop_delete()
    {
        // Silme Permissionsi varmı?
        if ($this->user->hasPermission('mod_page',  'delete', $this->lang['mod_page']['role_errors'][ 'delete'], 1) === False) {
            return False;
        }
        $sil = Req::Post_Safe( 'delete');
        if ($this->_dragdrop_delete($sil) === TRUE) {
            echo 'Oke';
        } else {
            echo 'ERROR';
            //print_r($this->mod_page_model);
        }
    }

    // Silme Fonksiyonu
    private function _dragdrop_delete($sil = NULL)
    {
        // mod_page Modeli Yükleyelim
        $this->mod_page_model = Load::model('mod_page');

        $this->mod_page_model->getid($sil)->getResultOne(TRUE, TRUE);
        if ($this->mod_page_model->getSize() === 0) {
            print($this->lang['mod_page']['errors']['record']);
            return FALSE;
        }
        $this->mod_page_model->sync();

        // Kayıt Başka birine ait ise Permissionsi varmı?
        if ($this->user->checkPermission('mod_page', 'moderate_delete', $this->mod_page_model->user_id, $this->lang['mod_page']['role_errors']['moderate_delete']) === False) {
            return False;
        }
        $new_model = new mod_page_model();
        if ($new_model->exists_parent_id($sil)) {
            foreach ($new_model->toArray() as $s) {
                if ($this->_dragdrop_delete($s['id']) === TRUE) {
                    continue;
                }
                return FALSE;
            }
        }


        if ($this->mod_page_model->image != '') {
            $image = Load::mod('image');
            $image->mod = 'mod_page';
            $image->name = 'image';
            $image->url = $this->mod_page_model->image;
            $option = [
                '/' =>
                [
                    'width' => 1200,
                    'height' => NULL,
                ],
                '/thumb' =>
                [
                    'width' => 250,
                    'height' => NULL,
                    'option' => 'width',
                ],
            ];
            if ($image->_delete_files($option) !== TRUE) {
                echo $image->error;
            }
        }


        $this->mod_page_model->getid($sil);
        // Modül varmı ve Gallery Ekleniyormu
        $this->mod = Load::model('mod_gallery_mod');
        if ($this->mod->exists_mod_name("mod_page") === TRUE) {
            // İçeriğe Ait Gallery Varsa Silelim
            Run::import('mod_gallery', '_remove', ['mod_page', $this->mod_page_model->id]);
        }
        // Modül varmı ve Dosya Ekleniyormu
        $this->mod = Load::model('mod_attachment_mod');
        if ($this->mod->exists_mod_name("mod_page") === TRUE) {
            // İçeriğe Ait Dosya Varsa Silelim
            Run::import('mod_attachment', '_remove', ['mod_page', $this->mod_page_model->id]);
        }

        $this->mod_page_model->delete();
        return TRUE;
    }



    // Valid Mesaj
    private function _oke_message($mesaj)
    {
        $this->tema->veri('oke', True);
        $this->tema->veri('mesaj', $mesaj);
    }

    // Hata Mesaj
    private function _error_message($mesaj)
    {
        $this->tema->veri('hata', True);
        $this->tema->veri('hatalar', [$mesaj]);
    }

    // Error Mesaj
    private function _error($mesaj)
    {
        $this->tema->veri('error', TRUE);
        $this->tema->veri('mesaj', $mesaj);
    }
}
