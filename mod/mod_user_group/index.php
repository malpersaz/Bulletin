<?php
/* AKM Software.
 * @2010
 * User group module
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
		// Load the admin template library.
		$this->tema = Load::lib('AdminTemplate');
		// Load the database library.
		$this->db = Load::lib('Database');
		// Site settings remain available through shared state when needed.
		//$this->site = Kayit::getir('site_config');
		// Load the module language file.
		$this->lang = Load::lang('mod_user_group');
		// Pass language data to the template layer.
		$this->tema->lang($this->lang);
		// Load the session library.
		$this->session = Load::lib('Session');
		// Load the user model.
		$this->user = Load::model('mod_user.user');
	}

	// Render the add form and handle creation.
	public function add()
	{
		// Check add permission.
		if ($this->user->hasPermission('mod_user_group', 'add', $this->lang['mod_user_group']['role_errors']['add'], 1) === False) {
			return False;
		}

		Run::import('management', '_index');

		if (Req::is_Post('add')) {
			$this->_add();
		}


		$this->site['title'] = $this->lang['mod_user_group']['admin']['1'];
		$this->tema->tpl('mod_user_group/add');
		$this->tema->veri('site', $this->site);
		$this->tema->veri('name', '');
		$this->tema->veri('parent_id', 0);

		$grup = Load::model('mod_user_group');
		$grup->order('order', 'ASC')->all();
		$gr = Helper::nested($grup->toArray(), 'parent_id', 'group_id');
		$this->tema->veri('user_groupm', $gr);
	}
	// Render the edit form and handle updates.
	public function edit($id = NULL)
	{
		// Check edit permission.
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
		$this->tema->veri('name', $d['name']);
		$this->tema->tpl('mod_user_group/edit');

		$grup->flush();
		$grup->order('order', 'ASC')->all();
		$gr = Helper::nested($grup->toArray(), 'parent_id', 'group_id');
		$this->tema->veri('user_groupm', $gr);
	}

	public function list()
	{

		// Check list permission.
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
	// Persist the visual order of user groups.
	public function order()
	{
		// Check move permission.
		if ($this->_hasMovePermission() === False) {
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


	// Move a user group under a new parent.
	public function move()
	{
		// Check move permission.
		if ($this->_hasMovePermission() === False) {
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




	// Handle add form submission.
	private function _add()
	{
		$this->val = Load::validator('mod_user_group.add');
		if ($this->val->check()) {
			foreach ($_POST as $key => $val) {
				$this->tema->veri($key, Req::Post_Safe($key));
			}
			$this->tema->veri('errors', $this->val->errors);
		} else {
			$this->model = Load::model('mod_user_group');
			foreach ($_POST as $key => $val) {
				$this->model->$key = Req::Post_Safe($key);
			}
			$this->model->user_id = $this->session->getir('id');
            
            // Default empty role payload to an empty JSON object.
            if (empty($this->model->rolls)) {
                $this->model->rolls = '{}';
            }

            // Default order to zero when no explicit position is submitted.
            if (empty($this->model->order)) {
                $this->model->order = 0;
            }

			// ACL mapping stays disabled here.
			//$this->model->acl=serialize(Req::Post_Safe('group_id'));

			if ($this->model->save()) {
				$this->_success($this->lang['mod_user_group']['admin']['12']);
			} else {
				$this->tema->veri('error', $this->model->db->sql_error());
			}
		}
	}
	// Handle edit form submission.
	private function _edit($id = NULL)
	{
		$this->val = Load::validator('mod_user_group.edit');
		if ($this->val->check($id)) {
			foreach ($_POST as $key => $val) {
				$this->tema->veri($key, Req::Post_Safe($key));
			}
			$this->tema->veri('errors', $this->val->errors);
		} else {
			$this->model = Load::model('mod_user_group');

			if (Req::is_Post('user_id')) {
				exit('!');
			}
			foreach ($_POST as $key => $val) {
				$this->model->$key = Req::Post_Safe($key);
			}
			$this->model->group_id = $id;

			// ACL mapping stays disabled here.
			//$this->model->acl=serialize(Req::Post_Safe('group_id'));

			if ($this->model->update('group_id')) {
				$this->_success($this->lang['mod_user_group']['admin']['13']);
			} else {
				$this->tema->veri('error', $this->model->db->sql_error());
			}
		}
	}

	// Delete a user group.
	public function delete()
	{
		// Check delete permission.
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
	// Delete the selected group and its descendants.
	private function _delete($id)
	{
		$this->user_group = Load::model('mod_user_group');

		$this->user_group->parent_id($id)->gR();

		$parent_id = $this->user_group->toArray();
		// Delete child groups recursively.
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
	// Check move permission and keep legacy Roll wiring intact.
	private function _hasMovePermission()
	{
		if (Roll::hasPermission('mod_user_group', 'move') === True) {
			return True;
		}

		return $this->user->hasPermission(
			'mod_user_group',
			'move',
			$this->lang['mod_user_group']['role_errors']['move']
		);
	}
	// Store a success message for the template.
	private function _success($message)
	{
		$this->tema->veri('success', True);
		$this->tema->veri('message', $message);
	}
	// Store an error message for the template.
	private function _error($message)
	{
		$this->tema->veri('error', True);
		$this->tema->veri('message', $message);
	}
}
