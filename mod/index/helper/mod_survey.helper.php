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
    /*
      * Anket
      */
    public function oyver()
    {
        $anket_id = Req::is_Post('anket_id') ? intval(Req::Post_Safe('anket_id')) : NULL;
        $secenek_id = Req::is_Post('secenek_id') ? intval(Req::Post_Safe('secenek_id')) : NULL;


        $secenek = Load::model('mod_survey.secenek');

        if ($anket_id != '' and $secenek_id != '') {

            if ($secenek_id != NULL && $secenek->exists_id($secenek_id) === False) {
                exit();
            }

            $anketip = Load::model('mod_survey.anketip');

            if ($anketip->exists_anket_id_and_ip($anket_id, $_SERVER['REMOTE_ADDR']) === False) {

                $secenek->getResultOne();
                $secenek->sync();
                // Seçeneğin Hitini Arttıralım
                $secenek->id = $secenek_id;
                $secenek->hit++;
                $secenek->update('id');
                // Oy Verenin İp Adresini Kaydedelim
                $anketip->anket_id = $anket_id;
                $anketip->ip = $_SERVER['REMOTE_ADDR'];
                $anketip->save();
                unset($anketip);
            }
        }

        $secenek->where_anket_id($anket_id)->gR();
        echo json_encode($secenek->toArray());
        unset($secenek);
    }

    public function _anket()
    {
        $anket = Load::model('mod_survey')->select('soru,id')->where_status_and_lang('y', $this->site['lang'])->gRo();
        if ($anket->getSize() !== 0) {


            $anket->sync();
            $sec = Load::model('mod_survey.secenek');
            $sec->where_anket_id($anket->id);
            $sec->gR();
            if ($sec->getSize() !== 0) {

                return  ['id' => $anket->id, 'soru' => $anket->soru, 'secenekler' => $sec->toArray()];
                // $this->tema->ctx_set('anket', array('id' => $anket->id, 'soru' => $anket->soru, 'secenekler' => $sec->toArray()));
            }
            unset($sec);
        }
        return false;
    }
}
