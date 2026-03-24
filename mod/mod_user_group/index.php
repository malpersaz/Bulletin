<?php
/* AKM Software.
 * @2010
 * Haber user_group Modül user_groupsı
 * mod/mod_user_group/index.php
 */
class mod_user_group_mod extends indexMain
{
	public $tema;
	public $site;
	public $lang;
	public $user;
	public $m;
	public $user_group;
	public $val;
	public $model;
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
		$this->lang = Load::lang('mod_user_group');
		// Dil Dosyasını Temaya Gönderelim
		$this->tema->lang($this->lang);
		// Session Kütüphanesi
		$this->session = Load::lib('Session');
		// User Model
		$this->user = Load::model('mod_user.user');
	}

	// Ekleme user_groupsı
	public function add()
	{
		// Ekleme Permissionsi varmı?
		if ($this->user->hasPermission('mod_user_group', 'add', $this->lang['mod_user_group']['role_errors']['add'], 1) === False) {
			return False;
		}

		Run::import('management', '_index');

		if (Req::is_Post('add')) {
			$this->_add();
		}


		$this->site['title'] = $this->lang['mod_user_group']['admin']['1'];
		$this->tema->tpl('mod_user_group/ekle');
		$this->tema->veri('site', $this->site);

		$grup = Load::model('mod_user_group');
		$grup->order('order', 'ASC')->all();
		$gr = Helper::nested($grup->toArray(), 'parent_id', 'group_id');
		$this->tema->veri('user_groupm', $gr);
	}
	// Düzenleme user_groupsı
	public function edit($id = NULL)
	{
		// Düzenleme Permissionsi varmı?
		if ($this->user->hasPermission('mod_user_group', 'edit', $this->lang['mod_user_group']['role_errors']['edit'], 1) === False) {
			return False;
		}


		$id = Req::Url_Clear($id);

		$grup = Load::model('mod_user_group');

		$grup->where_group_id($id)->limit(1)->gRo();
		if ($grup->getSize() === 0) {
			exit('');
		}
		$d = $grup->toArray();

		if ($this->user->isAuthorized('mod_user_group', $d['user_id'], $this->lang['mod_user_group']['role_errors']['1'], 1) === False) {
			return False;
		}

		if (Req::is_Post('edit')) {
			$this->_edit($id);
		}
		Run::import('management', '_index');

		$this->site['title'] = $this->lang['mod_user_group']['admin']['2'];
		$this->tema->veri('site', $this->site);
		$this->tema->veri('d', $d);
		$this->tema->tpl('mod_user_group/duzenle');

		$grup->flush();
		$grup->order('order', 'ASC')->all();
		$gr = Helper::nested($grup->toArray(), 'parent_id', 'group_id');
		$this->tema->veri('user_groupm', $gr);
	}

	public function list()
	{

		// Listeleme Permissionsi varmı?
		if ($this->user->hasPermission('mod_user_group', 'list', $this->lang['mod_user_group']['role_errors']['list'], 1) === False) {
			return False;
		}
		Run::import('management', '_index');

		$grup = Load::model('mod_user_group');

		$grup->order('order', 'ASC')->all();
		$gr = Helper::nested($grup->toArray(), 'parent_id', 'group_id');
		$this->tema->veri('user_groupm', $gr);



		$this->site['title'] = $this->lang['mod_user_group'][1];
		$this->tema->veri('site', $this->site);

		$this->tema->tpl('mod_user_group/list');
	}
	// user_group Sıra
	public function sira()
	{
		// Taşıma Permissionsi varmı?
		if ($this->user->hasPermission('mod_user_group', 'tasi', $this->lang['mod_user_group']['role_errors']['tasi']) === False) {
			return False;
		}
		$b = Req::Post_Safe('order');
		$this->m = Load::model('mod_user_group');
		$x = 1;
		foreach ($b as $a) {
			$this->m->group_id = $a;
			$this->m->order = $x;
			if ($this->m->update('group_id') === False) {
				print_r($this->m, TRUE);
				return;
			}
			$x++;
		}
		echo 'OK';
	}


	// user_group Taşıma
	public function tasi()
	{
		// Taşıma Permissionsi varmı?
		if ($this->user->hasPermission('mod_user_group', 'tasi', $this->lang['mod_user_group']['role_errors']['tasi']) === False) {
			return False;
		}
		$this->user_group = Load::model('mod_user_group');
		$this->user_group->group_id = Req::Post_Safe('group_id');
		$this->user_group->parent_id = Req::Post_Safe('parent_id');
		if ($this->user_group->update('group_id')) {
			echo 'OK';
		} else {
			echo $this->user_group->db->sql_text();
		}
	}




	// Ekleme Fonksiyonu
	private function _add()
	{
		$this->val = Load::validator('mod_user_group.ekle');
		if ($this->val->check()) {
			foreach ($_POST as $key => $val) {
				$this->tema->veri($key, Req::Post_Safe($key));
			}
			$this->tema->veri('hatalar', $this->val->hatalar);
		} else {
			$this->model = Load::model('mod_user_group');
			foreach ($_POST as $key => $val) {
				$this->model->$key = Req::Post_Safe($key);
			}
			$this->model->user_id = $this->session->getir('id');

			// ACL
			//$this->model->acl=serialize(Req::Post_Safe('group_id'));

			if ($this->model->save()) {
				$this->_mesaj($this->lang['mod_user_group']['admin']['12']);
			} else {
				$this->tema->veri('hata', $this->model->db->sql_error());
			}
		}
	}
	// Düzenleme Fonksiyonu
	private function _edit($id = NULL)
	{
		$this->val = Load::validator('mod_user_group.duzenle');
		if ($this->val->check($id)) {
			foreach ($_POST as $key => $val) {
				$this->tema->veri($key, Req::Post_Safe($key));
			}
			$this->tema->veri('hatalar', $this->val->hatalar);
		} else {
			$this->model = Load::model('mod_user_group');

			if (Req::is_Post('user_id')) {
				exit('!');
			}
			foreach ($_POST as $key => $val) {
				$this->model->$key = Req::Post_Safe($key);
			}
			$this->model->group_id = $id;

			// ACL
			//$this->model->acl=serialize(Req::Post_Safe('group_id'));

			if ($this->model->update('group_id')) {
				$this->_mesaj($this->lang['mod_user_group']['admin']['13']);
			} else {
				$this->tema->veri('hata', $this->model->db->sql_error());
			}
		}
	}

	// Silme Fonksiyonu
	public function sil()
	{
		// Silme Permissionsi varmı?
		if ($this->user->hasPermission('mod_user_group',  'delete', $this->lang['mod_user_group']['role_errors'][ 'delete']) === False) {
			return False;
		}
		$this->user_group = Load::model('mod_user_group');
		$s = Req::Post_Safe('group_id');

		$this->user_group->Getgroup_id($s)->gRo();
		$d = $this->user_group->toObject();

		if ($this->user->isAuthorized('mod_user_group', $d->user_id, $this->lang['mod_user_group']['role_errors']['2']) === False) {
			return False;
		}

		if ($this->_delete($s)) {
			echo 'OK';
		} else {
			echo 'Error';
		}
	}
	// Silme Fonksiyonu
	private function _delete($id)
	{
		$this->user_group = Load::model('mod_user_group');

		$this->user_group->parent_id($id)->gR();

		$parent_id = $this->user_group->toArray();
		// Alt Menülerde Siliniyor.
		if (is_array($parent_id)) {
			foreach ($parent_id as $r) {
				$this->user_group->where_group_id($r['group_id'])->delete();

				if ($this->user_group->exists_parent_id($r['group_id'])) {
					$this->_delete($r['group_id']);
				}
			}
		}
		$this->user_group->where_group_id($id)->delete();
		return TRUE;
	}
	// Valid Mesaj
	private function _mesaj($mesaj)
	{
		$this->tema->veri('oke', True);
		$this->tema->veri('mesaj', $mesaj);
	}
	// Hata Mesaj
	private function _emesaj($mesaj)
	{
		$this->tema->veri('hata', True);
		$this->tema->veri('mesaj', $mesaj);
	}
}
