<?php
/* AKM Software
 * @ 2011
 * index Base
 * mod/indexBase/index.php
 */
abstract class indexBase
{
    public $tema;
    public $site;
    public $lang;
    public $session;
    public $db;
    public $user;
    public $val;
    public $mod;
    public $model;
    public $secenek;
    public $sec;
    public function __construct()
    {
        $this->tema = Load::lib('TwigTemplate');
        // print_r($this->tema);
        $this->site = Kayit::getir('site_config');
        $this->_get_site_settings(); // Call the new site settings loader
        $this->tema->setGlobal('site', $this->site);
        //print_r($this->site);
        $this->lang = Load::lang('index');
        $this->tema->ctx_set('tarih', Helper::format_date(time()));
        $this->tema->lang($this->lang);
        $this->session=Load::lib('Session');
        //print_r($_SESSION);
    }

        public function _index()
    {
        $this->_site();
    //  $this->tema->tpl('index/index');
    //  $this->tema->loadFilter('output', 'tidy');
    //  $this->tema->loadFilter ( 'output', 'trimwhitespace' );
    //  if ($this->tema->getVeri('tpl') === NULL) {
    //     $this->tema->tpl('');
    //  }
    //  print_r($this->tema);
    }


    // ACL'nin dahil olduğu grupları getir
    public function _getAcl($acl_id)
    {
        Cache::$sure = 60 * 60 * 24;
        if (Cache::exists_cache("permission - acl - " . $acl_id)) {
            return Cache::get_cache();
        }
        $acl = Load::model('mod_acl');
        $acl->where_id($acl_id)->gRo();
        // Böyle bir acl yok
        if ($acl->getSize() === 0) {
            return FALSE;
        }
        $acl->sync();
        Cache::set(unserialize($acl->acl), "permission - acl - " . $acl_id);
        return unserialize($acl->acl);
    }



    // Gallery Images to Theme
    public function _gallery_resimgetir($mod, $vid)
    {
        $gallery = Load::model('mod_gallery');
        $gallery->where_mod_name_and_vid($mod, $vid);
        $gallery->select('id,url,name')->order('id', 'DESC');
        $gallery->gR();

        if ($gallery->getSize() !== 0) {
            $this->tema->ctx_set('res', $gallery->toArray());
        }
        unset($gallery);
    }

    public function _mod_attachment($mod, $vid)
    {
        $file = Load::model('mod_attachment');
        $file->where_mod_name_and_vid($mod, $vid);
        $file->select('id,url,original_name,filename')->order('id', 'DESC');
        $file->gR();

        if ($file->getSize() !== 0) {
            $this->tema->ctx_set('file', $file->toArray());
        }
        unset($file);
    }

    public function _exact_attachment($id)
    {
        $file = Load::model('mod_attachment');
        $file->where_id($id);
        $file->select('id,url,original_name,filename')->gRo();
        
        if ($file->getSize() !== 0) {
            $this->tema->ctx_set('file', [$file->toArray()]);
        }
        unset($file);
    }

    public function _exact_gallery($id)
    {
        $gallery = Load::model('mod_gallery');
        $gallery->where_id($id);
        $gallery->select('id,url,name')->gRo();

        if ($gallery->getSize() !== 0) {
            $this->tema->ctx_set('res', [$gallery->toArray()]);
        }
        unset($gallery);
    }

    public function _mod_video($v)
    {
        $data = unserialize($v);
        if (!is_array($data) || empty($data)) {
            return;
        }

        $video_ids = [];
        $vid_ids = [];

        foreach($data as $prefixed_id) {
            $parts = explode(':', $prefixed_id, 2);
            if (count($parts) == 2) {
                if ($parts[0] == 'video') {
                    $video_ids[] = $parts[1];
                } elseif ($parts[0] == 'vid') {
                    $vid_ids[] = $parts[1];
                }
            }
        }

        $results = [];

        if (!empty($video_ids)) {
            $video_model = Load::model('mod_video');
            $video_model->where_id($video_ids)->gR();
            if ($video_model->getSize() > 0) {
                $results = array_merge($results, $video_model->toArray());
            }
        }

        if (!empty($vid_ids)) {
            $vid_model = Load::model('mod_video_embed');
            $vid_model->select('id, name, embed, image'); // Fetch embed and resim (poster)
            $vid_model->where_id($vid_ids)->gR();
            if ($vid_model->getSize() > 0) {
                $results = array_merge($results, $vid_model->toArray());
            }
        }

        $this->tema->ctx_set('video', $results);
    }

    public function _mod_ust_list($mod, $id, $select, $order)
    {

        if ($id == 0) {
            return false;
        }
        $list = Load::model($mod);
        $list->flush();
        $list->addWhere(['id' => $id], ' OR');
        $list->addWhere(['parent_id' => $id], ' OR');

        $list->select($select)->order($order, 'ASC');
        $list->gR();
        if ($list->getSize() !== 0) {


            $myArray = [];
            foreach ($list->toArray() as $ar) {
                $myArray[$ar[$order]] = $ar;
            }
            $this->tema->ctx_set('list', $myArray);
            $this->tema->ctx_set('list_root', $id);
        }
        unset($list);
    }

    public function _mod_parent_id_list($mod, $id, $select, $order)
    {

        if ($id == 0) {
            return false;
        }
        $list = Load::model($mod);
        $list->flush();
        //$list->addWhere(array('id'=>$id),' OR');
        $list->addWhere(['parent_id' => $id], ' OR');

        $list->select($select)->order($order, 'ASC');
        $list->gR();
        if ($list->getSize() !== 0) {


            $myArray = [];
            foreach ($list->toArray() as $ar) {
                $myArray[$ar[$order]] = $ar;
            }
            $this->tema->ctx_set('parent_id_list', $myArray);
            $this->tema->ctx_set('parent_id_list_root', $id);
        }
        unset($list);
    }

    public function _site()
    {
        //$this->site['title'] = $this->lang['index']['title'];
        //$this->site['keywords'] = $this->lang['index']['keywords'];
        //$this->site['description'] = $this->lang['index']['description'];



        //print_r($this->site);
       // $this->tema->setGlobal('site', $this->site);
    }

    /**
     * Fetch site-wide settings for current language and add to site variables
     * This replaces the old _get_locale_settings.
     */
    private function _get_site_settings()
    {
        $current_lang = $this->site['lang'] ?? 'tr';
        
        $settings_model = Load::model('mod_settings');
        $settings_model->where_lang($current_lang)->gRo();
        
        if ($settings_model->getSize() > 0) {
            $settings_model->sync();
            $settings_data = $settings_model->getResultOne()->toArray();

            // Direct assignment with only necessary transformation
            foreach ($settings_data as $key => $value) {
                $final_key = ($key === 'email') ? 'mail' : $key;
                $this->site[$final_key] = $value;
            }
        }

        // Load active social media links
        Load::modelfile('mod_settings.mod_settings_social');
        $social_model = new mod_settings_social_model();
        $social_model->where_is_active('y')->order('display_order', 'ASC')->gR();
        $this->site['social_links'] = ($social_model->getSize() > 0) ? $social_model->toArray() : [];
    }

    public function _parent_id_pages($id)
    {
        Load::modelfile('mod_page');
        $sub_page = new mod_page_model();
        $sub_page->where_parent_id_and_status_and_root_and_lang($id, 'y', 'n', $this->site['lang']);
        $sub_page->select('name,summary,seo');
        $sub_page->order('order', 'ASC');
        $sub_page->gR();
        if ($sub_page->getSize() !== 0) {
            $this->tema->ctx_set('sub_pages', $sub_page->toArray());
        }
        unset($sub_page);
    }

    public function _get_category_news($category_seo, $limit = 5)
    {
        $news = Load::model('mod_news.newses');
        $news->where_category_seo_and_status_and_lang($category_seo, 'y', $this->site['lang']);
        $news->order('created_at', 'DESC');
        $news->limit($limit);
        $news->gR();
        
        if ($news->getSize() !== 0) {
            $items = $news->toArray();
            foreach ($items as &$item) {
                $item['has_video'] = (!empty($item['video']) && $item['video'] != 'a:0:{}');
                $item['has_gallery'] = (isset($item['gallery_id']) && $item['gallery_id'] != 0);
                $item['has_file'] = (isset($item['file_id']) && $item['file_id'] != 0);
            }
            return $items;
        }
        return [];
    }

    public function _related_content($category_id, $limit = 5)
    {
        $content = Load::model('mod_content.contents');
        $content->where_category_id_and_status($category_id, 'y');
        $content->select('name,seo,summary,image');
        $content->order('created_at', 'DESC');
        $content->limit($limit);
        $content->gR();
        if ($content->getSize() !== 0) {
            $this->tema->ctx_set('contents', $content->toArray());
            $content->getResultOne();
            $this->tema->ctx_set('son_icerik', $content->toArray());
        }
        unset($content);
    }

    public function _related_images($id)
    {
        if ($id == 0) {
            return;
        }
        $r = Load::model('mod_imagecategories');
        $r->select('name,seo,description');
        $r->where_parent_id_and_lang_and_root($id, $this->site['lang'], 'n');
        $r->order('order', 'DESC');
        $r->gR();
        if ($r->getSize() !== 0) {
            $this->tema->ctx_set('pictures', $r->toArray());
        }
        unset($r);
    }


    public function _permission_kontrol($acl_id)
    {
        if ($acl_id === 0) {
            return false;
        }
        $acl = $this->_getAcl($acl_id);

        if ($acl === false) {
            return false;
        }

        $this->session = Load::lib('Session');
        // Eğer Üye Grubu Yoksa Üye Grubu 0'dır yani ziyaretçi
        $grup = $this->session->getir('group_id') !== false ? $this->session->getir('group_id') : 0;
        if (!in_array($grup, $acl)) {
            $this->tema->tpl('index/register');
            $this->_index();

            return true;
        }

        return false;
    }




    public function _404()
    {
        header("HTTP/1.0 404 Not Found");
        header("Status: 404 Not Found");
        Helper::yonlendir($this->site['adres']);
        exit();
    }

    /**
     * IP Bazlı Görüntüleme Sayısını Günceller (Anti-F5 Spam)
     * @param string $table Tablo adı (Örn: mod_news)
     * @param int $id İçerik ID'si
     * @return bool
     */
    public function _update_view_count($table, $id)
    {
        if (empty($table) || empty($id)) {
            return false;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $today = date('Y-m-d');
        
        $db = Load::lib('Database');
        
        // Bu IP bugün bu içeriği görmüş mü kontrol et
        $check = $db->query("SELECT id FROM `sys_view_logs` WHERE `table_name` = '$table' AND `item_id` = " . intval($id) . " AND `ip_address` = '$ip' AND `created_at` = '$today' LIMIT 1");
        
        if ($db->getSize() === 0) {
            // Log kaydı ekle (Duplicate entry hatasını önlemek için IGNORE kullanıyoruz)
            $db->query("INSERT IGNORE INTO `sys_view_logs` (`table_name`, `item_id`, `ip_address`, `created_at`) 
                        VALUES ('$table', " . intval($id) . ", '$ip', '$today')");
            
            // Eğer bir satır eklendiyse view count artır
            if ($db->affected_rows() > 0) {
                $db->query("UPDATE `$table` SET `view_count` = `view_count` + 1 WHERE `id` = " . intval($id));
                return true;
            }
        }
        
        return false;
    }
}
