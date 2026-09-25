<?php
/* AKM Software.
 * @2015 - Modernized in 2026
 * mod_survey Module Page
 * mod/mod_survey/index.php
 */

class mod_survey_mod extends BaseModuleController
{
    public $mod_survey_model;

    // Configuration for data loading
    protected $need_simple_data = [
        'lang' => 'lang_lang',
        'mod_acl' => 'mod_acl_acl_id'
    ];

    public function __construct()
    {
        parent::__construct('mod_survey');
    }

    protected function getDatatableColumns()
    {
        return [
            'id',
            'question|bold',
            'lang|badge',
            'status|badge'
        ];
    }

    public function edit($id = null)
    {
        $id_clean = Req::Url_Clear($id);
        $optionsModel = Load::model('mod_survey.surveyselect');
        $optionsModel->where_survey_id($id_clean)->gR();
        $this->theme->set('options_list', $optionsModel->toArray());

        return parent::edit($id);
    }

    // Callbacks for option management
    protected function afterSave($model)
    {
        $options = Req::Post_Safe('options');
        if (is_array($options)) {
            $optionsModel = Load::model('mod_survey.surveyselect');
            foreach ($options as $opt_text) {
                if (trim($opt_text) !== '') {
                    $optionsModel->flush();
                    $optionsModel->survey_id = $model->id;
                    $optionsModel->option_text = trim($opt_text);
                    $optionsModel->hit = 0;
                    $optionsModel->save();
                }
            }
        }
        return $model;
    }

    protected function afterUpdate($model, $id)
    {
        $options = Req::Post_Safe('options');
        $optionsModel = Load::model('mod_survey.surveyselect');
        
        // Load existing options
        $optionsModel->where_survey_id($id)->gR();
        $existing = [];
        if ($optionsModel->getSize() > 0) {
            foreach ($optionsModel->toArray() as $ex) {
                $existing[$ex['id']] = $ex;
            }
        }

        $valid_ids = [];
        if (is_array($options)) {
            foreach ($options as $opt_id => $opt_text) {
                if (trim($opt_text) !== '') {
                    $optionsModel->flush();
                    if (is_numeric($opt_id) && isset($existing[$opt_id])) {
                        // Update existing option
                        $optionsModel->option_text = trim($opt_text);
                        $optionsModel->id = $opt_id;
                        $optionsModel->update('id');
                        $valid_ids[] = $opt_id;
                    } else {
                        // Add new option
                        $optionsModel->survey_id = $id;
                        $optionsModel->option_text = trim($opt_text);
                        $optionsModel->hit = 0;
                        $optionsModel->save(true);
                        $valid_ids[] = $optionsModel->id;
                    }
                }
            }
        }

        // Delete removed options
        $optionsModel->flush();
        if (count($valid_ids) > 0) {
            $optionsModel->where_survey_id_and_not_id($id, $valid_ids)->delete();
        } else {
            $optionsModel->where_survey_id($id)->delete();
        }

        return $model;
    }

    protected function afterDelete($id)
    {
        // Delete options
        $optionsModel = Load::model('mod_survey.surveyselect');
        $optionsModel->where_survey_id($id)->delete();

        // Delete voting IPs
        $ip = Load::model('mod_survey.surveyip');
        $ip->where_survey_id($id)->delete();

        return true;
    }

    // Set Default action for AJAX request
    public function set_default()
    {
        if ($this->user->hasPermission('mod_survey', 'moderate_edit', $this->lang['mod_survey']['permission_errors']['edit_denied']) === false) {
            return false;
        }

        $id = intval(Req::Post_Safe('id'));
        if ($id > 0) {
            $model = Load::model('mod_survey');
            $model->where_id($id)->gRo();
            if ($model->getSize() > 0) {
                $model->sync();
                
                // Set status of all other polls in the same language to 'n'
                $this->db->query("UPDATE `mod_survey` SET `status`='n' WHERE `lang`='" . $this->db->clear($model->lang) . "'");

                // Set status of this poll to 'y'
                $model->status = 'y';
                $model->id = $id;
                if ($model->update('id')) {
                    echo 'Success';
                    return;
                }
            }
        }
        echo 'Error';
    }
}
