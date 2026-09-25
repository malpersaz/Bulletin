<?php

class mod_survey_helper extends indexBase
{
    public function __construct()
    {
        parent::__construct();
    }

    /*         if ($anket_id === NULL || $secenek_id === NULL) {
            return FALSE;
        }
        if (!intval($anket_id)  || !intval($secenek_id)) {
            return FALSE;
        } */
    /**
     * Submit vote for survey option
     */
    public function vote()
    {
        $survey_id = Req::is_Post('anket_id') ? intval(Req::Post_Safe('anket_id')) : (Req::is_Post('survey_id') ? intval(Req::Post_Safe('survey_id')) : NULL);
        $option_id = Req::is_Post('secenek_id') ? intval(Req::Post_Safe('secenek_id')) : (Req::is_Post('option_id') ? intval(Req::Post_Safe('option_id')) : NULL);

        $options = Load::model('mod_survey.secenek');

        if ($survey_id != '' && $option_id != '') {

            if ($option_id != NULL && $options->exists_id($option_id) === False) {
                exit();
            }

            $survey_ip = Load::model('mod_survey.anketip');

            if ($survey_ip->exists_anket_id_and_ip($survey_id, $_SERVER['REMOTE_ADDR']) === False) {

                $options->getResultOne();
                $options->sync();
                // Increment option hit counter
                $options->id = $option_id;
                $options->hit++;
                $options->update('id');
                // Record voter IP address
                $survey_ip->anket_id = $survey_id;
                $survey_ip->ip = $_SERVER['REMOTE_ADDR'];
                $survey_ip->save();
                unset($survey_ip);
            }
        }

        $options->where_anket_id($survey_id)->gR();
        echo json_encode($options->toArray());
        unset($options);
    }

    public function oyver()
    {
        return $this->vote();
    }

    public function _survey()
    {
        $survey = Load::model('mod_survey')->select('soru,id')->where_status_and_lang('y', $this->site['lang'])->gRo();
        if ($survey->getSize() !== 0) {

            $survey->sync();
            $options = Load::model('mod_survey.secenek');
            $options->where_anket_id($survey->id);
            $options->gR();
            if ($options->getSize() !== 0) {

                return ['id' => $survey->id, 'soru' => $survey->soru, 'question' => $survey->soru, 'secenekler' => $options->toArray(), 'options' => $options->toArray()];
            }
            unset($options);
        }
        return false;
    }

    public function _anket()
    {
        return $this->_survey();
    }
}
