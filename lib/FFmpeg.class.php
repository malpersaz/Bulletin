<?php
class FFmpeg
{
    public $_hatalar = [];
    public $ffmpeg = '/usr/bin/ffmpeg';
    public $flvtool2 = '/usr/bin/flvmeta';
    public $mencoder = '/usr/bin/mencoder';
    public $video_dir = NULL;
    public $file = NULL;
    public $image_dir = NULL;
    public $video_output = NULL;
    public $image_output = NULL;
    public $tmp = NULL;
    public $log = NULL;
    public $komut = NULL;
    public $sonuc = [
        'video_process' => NULL,
        'video_original' => NULL,
        'video_command' => NULL,
        'video_file' => NULL,
        'video_duration' => NULL
    ];
    public $ar = NULL; // -ar
    public $ab = NULL; // -ab
    public $aspect = NULL; // --aspect
    public $b = NULL; // -b
    public $r = NULL; // -r
    public $f = NULL; // -f
    public $s = NULL; // -s
    public $acodec = NULL; // -acodec
    public $ac = NULL; // -ac

    public $y = '-y'; // -ac


    /* Resim Caps */
    public $image_w = NULL;
    public $image_h = NULL;


    public function flv()
    {
        if (!file_exists($this->ffmpeg)) {
            $this->_hatalar [] = 'FFMPEG BULUNAMADI!';
            return False;
        }
        if (!file_exists($this->flvtool2)) {
            $this->_hatalar [] = 'flvtool2 BULUNAMADI!';
            return False;
        }
        if (!is_dir($this->video_dir)) {
            $this->_hatalar [] = 'FLV OUTPUT Dizini bulunamadı!';
            return False;
        }

        if (!is_dir($this->tmp)) {
            $this->_hatalar [] = '$this->tmp Bir Dizin Değil!' . $this->tmp;
            return False;
        }

        if (!is_dir($this->log)) {
            $this->_hatalar [] = '$this->log Bir Dizin Değil!' . $this->log;
            return False;
        }


        if ($this->file == NULL) {
            $this->_hatalar [] = 'Dönüştürülecek Dosyayı Belirtin $this->file';
            return False;
        }
        if ($this->video_output == NULL) {
            $this->_hatalar [] = 'Çıktı Oluşturulacak FLV Dosyasını Belirtin!';
            return False;
        }


        $komut = [];
        $komut[] = $this->ffmpeg;
        $komut[] = '-i ' . $this->file;
        $komut[] = $this->ar != NULL ? '-ar ' . $this->ar : '-ar 44100';
        $komut[] = $this->ab != NULL ? '-ab ' . $this->ab : '-ab 128';
        $komut[] = $this->aspect != NULL ? '-aspect ' . $this->aspect : '-aspect 4:3';
        $komut[] = $this->b != NULL ? '-b ' . $this->b : '-b 200';
        $komut[] = $this->r != NULL ? '-r ' . $this->r : '-r 12';
        $komut[] = $this->f != NULL ? '-f ' . $this->f : '-f flv';
        $komut[] = $this->s != NULL ? '-s ' . $this->s : '-s 512x320';
        $komut[] = $this->acodec != NULL ? '-acodec ' . $this->acodec : '-acodec libmp3lame';
        $komut[] = $this->ac != NULL ? '-ac ' . $this->ac : '-ac 1';
        $komut[] = $this->y != NULL ? '-y' : '';
        $komut[] = $this->video_dir . $this->video_output;
        $komut[] = '> ' . $this->log . basename($this->file) . '.txt 2>&1';

        $this->komut = implode(' ', $komut);
        $this->komut .= ';' . $this->flvtool2 . ' -U ' . $this->video_dir . $this->video_output . ' > ' . $this->log . basename($this->file) . '_flvtool2.txt 2>&1';

        $start = microtime();
        $start = explode(" ", $start);
        $start = $start[1] + $start[0];


        exec($this->komut);

        $end = microtime();
        $end = explode(" ", $end);
        $end = $end[1] + $end[0];

        $gecen_sure = $end - $start;

        // FLV DOSYASI
        if (!file_exists($this->video_dir . $this->video_output) OR filesize($this->video_dir . $this->video_output) === 0) {
            $this->_hatalar [] = 'FLV Dosyası Dönüştürülemedi Log Dosyası:' . $this->log . basename($this->file) . '.txt';
            return False;
        }

        $flvtool2_log = file_get_contents($this->log . basename($this->file) . '_flvtool2.txt');
        if (strpos($flvtool2_log, 'ERROR:') !== False) {
            $this->_hatalar [] = 'FLVTOOL2 İle Meta Taglar Güncellenemedi Log Dosyası:' . $this->log . basename($this->file) . '_flvtool2.txt';
            return False;
        }

        $log = file_get_contents($this->log . basename($this->file) . '.txt');
        preg_match('/Duration: ([\d][\d]):([\d][\d]):([\d][\d]).([\d]+)/', $log, $cikti);
        $sure[] = $cikti['1'];
        $sure[] = $cikti['2'];
        $sure[] = $cikti['3'];
        $sure = implode(':', $sure);

        $this->sonuc ['video_process'] = $gecen_sure;
        $this->sonuc ['video_duration'] = $sure;
        $this->sonuc ['video_file'] = $this->video_output;
        $this->sonuc ['video_command'] = $this->komut;
        $this->sonuc['video_original'] = basename($this->file);
        file_put_contents($this->log . basename($this->file) . '.txt', "\n" . $this->komut, FILE_APPEND | LOCK_EX);
        return True;
        //$komut.='ffmpeg -i FNY8ig8aXj.wmv -ar 44100 -ab 128 -aspect 4:3 -b 200 -r 12 -f flv -s 512x320 -acodec libmp3lame -ac 1 FNY8ig8aXj.flv';
    }

    public function make_mp4_to_flv()
    {

        /*if(!file_exists($this->ffmpeg)){
              $this->_hatalar [] = 'FFMPEG BULUNAMADI!';
              return False;
          }
          if(!file_exists($this->flvtool2)){
              $this->_hatalar [] = 'flvtool2 BULUNAMADI!';
              return False;
          }*/


        if (!is_dir($this->video_dir)) {
            $this->_hatalar [] = 'FLV OUTPUT Dizini bulunamadı!';
            return False;
        }

        if (!is_dir($this->tmp)) {
            $this->_hatalar [] = '$this->tmp Bir Dizin Değil!' . $this->tmp;
            return False;
        }
        if (!is_dir($this->log)) {
            $this->_hatalar [] = '$this->log Bir Dizin Değil!' . $this->log;
            return False;
        }

        if ($this->file == NULL) {
            $this->_hatalar [] = 'Dönüştürülecek Dosyayı Belirtin $this->file';
            return False;
        }
        if ($this->video_output == NULL) {
            $this->_hatalar [] = 'Çıktı Oluşturulacak FLV Dosyasını Belirtin!';
            return False;
        }

        //$movie = new ffmpeg_movie($this->file, FALSE);
        
        $this->s='copy';
        $komut = [];
        $komut[] = $this->ffmpeg;
        $komut[] = '-i ' . $this->file;
        $komut[] = '-vcodec copy -acodec copy';
        $komut[] = $this->f != NULL ? '-f ' . $this->f : '-f flv';
        $komut[] = $this->s != NULL ? '-s ' . $this->s : '';
        //$komut[] = $this->s != NULL ? '-s ' . $this->s : '-s ' . $this->makeMultipleTwo($movie->getFrameWidth()) . 'x' . $this->makeMultipleTwo($movie->getFrameHeight());

        //$komut[] = $this->acodec != NULL ? '-acodec ' . $this->acodec : '-acodec libmp3lame';
        //$komut[] = $this->ac != NULL ? '-ac ' . $this->ac : '-ac 1';

        $komut[] = $this->y != NULL ? '-y' : '';
        $komut[] = $this->video_dir . $this->video_output;
        $komut[] = '> ' . $this->log . basename($this->file) . '.txt 2>&1';

        $this->komut = implode(' ', $komut);
        $this->komut .= ';' . $this->flvtool2 . ' -U ' . $this->video_dir . $this->video_output . ' > ' . $this->log . basename($this->file) . '_flvtool2.txt 2>&1';

        $start = microtime();
        $start = explode(" ", $start);
        $start = $start[1] + $start[0];


        exec($this->komut);

        $end = microtime();
        $end = explode(" ", $end);
        $end = $end[1] + $end[0];

        $gecen_sure = $end - $start;

        // FLV DOSYASI
        if (!file_exists($this->video_dir . $this->video_output) OR filesize($this->video_dir . $this->video_output) === 0) {
            $this->_hatalar [] = 'FLV Dosyası Dönüştürülemedi Log Dosyası:' . $this->log . basename($this->file) . '.txt';
            return False;
        }

        $flvtool2_log = file_get_contents($this->log . basename($this->file) . '_flvtool2.txt');
        if (strpos($flvtool2_log, 'ERROR:') !== False) {
            $this->_hatalar [] = 'FLVTOOL2 İle Meta Taglar Güncellenemedi Log Dosyası:' . $this->log . basename($this->file) . '_flvtool2.txt';
            return False;
        }

        $log = file_get_contents($this->log . basename($this->file) . '.txt');
        preg_match('/Duration: ([\d][\d]):([\d][\d]):([\d][\d]).([\d]+)/', $log, $cikti);
        $sure[] = $cikti['1'];
        $sure[] = $cikti['2'];
        $sure[] = $cikti['3'];
        $sure = implode(':', $sure);

        $this->sonuc ['video_process'] = $gecen_sure;
        $this->sonuc ['video_duration'] = $sure;
        $this->sonuc ['video_file'] = $this->video_output;
        $this->sonuc ['video_command'] = $this->komut;
        $this->sonuc['video_original'] = basename($this->file);
        file_put_contents($this->log . basename($this->file) . '.txt', "\n" . $this->komut, FILE_APPEND | LOCK_EX);
        return True;
    }

    // Hiç Bir Ayar Yollamadan
    function flv_yap()
    {
        /*if(!file_exists($this->ffmpeg)){
			$this->_hatalar [] = 'FFMPEG BULUNAMADI!';
			return False;
		}
		if(!file_exists($this->flvtool2)){
			$this->_hatalar [] = 'flvtool2 BULUNAMADI!';
			return False;
		}*/


        if (!is_dir($this->video_dir)) {
            $this->_hatalar [] = 'FLV OUTPUT Dizini bulunamadı!';
            return False;
        }

        if (!is_dir($this->tmp)) {
            $this->_hatalar [] = '$this->tmp Bir Dizin Değil!' . $this->tmp;
            return False;
        }

        if (!is_dir($this->log)) {
            $this->_hatalar [] = '$this->log Bir Dizin Değil!' . $this->log;
            return False;
        }

        if ($this->file == NULL) {
            $this->_hatalar [] = 'Dönüştürülecek Dosyayı Belirtin $this->file';
            return False;
        }
        if ($this->video_output == NULL) {
            $this->_hatalar [] = 'Çıktı Oluşturulacak FLV Dosyasını Belirtin!';
            return False;
        }

        //$movie = new ffmpeg_movie($this->file,TRUE);

        $komut = [];
        $komut[] = $this->ffmpeg;
        $komut[] = '-i ' . $this->file;
        $komut[] = $this->f != NULL ? '-f ' . $this->f : '-f flv';
        $komut[] = $this->s != NULL ? '-s ' . $this->s : '-s copy';
        $komut[] = $this->y != NULL ? '-y' : '';
        $komut[] = $this->video_dir . $this->video_output;
        $komut[] = '> ' . $this->log . basename($this->file) . '.txt 2>&1';

        $this->komut = implode(' ', $komut);
        $this->komut .= ';' . $this->flvtool2 . ' -U ' . $this->video_dir . $this->video_output . ' > ' . $this->log . basename($this->file) . '_flvtool2.txt 2>&1';

        $start = microtime();
        $start = explode(" ", $start);
        $start = $start[1] + $start[0];


        exec($this->komut);

        $end = microtime();
        $end = explode(" ", $end);
        $end = $end[1] + $end[0];

        $gecen_sure = $end - $start;

        // FLV DOSYASI
        if (!file_exists($this->video_dir . $this->video_output) OR filesize($this->video_dir . $this->video_output) === 0) {
            $this->_hatalar [] = 'FLV Dosyası Dönüştürülemedi Log Dosyası:' . $this->log . basename($this->file) . '.txt';
            return False;
        }

        $flvtool2_log = file_get_contents($this->log . basename($this->file) . '_flvtool2.txt');
        if (strpos($flvtool2_log, 'ERROR:') !== False) {
            $this->_hatalar [] = 'FLVTOOL2 İle Meta Taglar Güncellenemedi Log Dosyası:' . $this->log . basename($this->file) . '_flvtool2.txt';
            return False;
        }

        $log = file_get_contents($this->log . basename($this->file) . '.txt');
        preg_match('/Duration: ([\d][\d]):([\d][\d]):([\d][\d]).([\d]+)/', $log, $cikti);
        $sure[] = $cikti['1'];
        $sure[] = $cikti['2'];
        $sure[] = $cikti['3'];
        $sure = implode(':', $sure);

        $this->sonuc ['video_process'] = $gecen_sure;
        $this->sonuc ['video_duration'] = $sure;
        $this->sonuc ['video_file'] = $this->video_output;
        $this->sonuc ['video_command'] = $this->komut;
        $this->sonuc['video_original'] = basename($this->file);
        file_put_contents($this->log . basename($this->file) . '.txt', "\n" . $this->komut, FILE_APPEND | LOCK_EX);
        return True;
    }

    public function resim()
    {

        /*if(!file_exists($this->ffmpeg)){
              $this->_hatalar [] = 'FFMPEG BULUNAMADI!';
              return False;
          }*/

        if (!is_dir($this->tmp)) {
            $this->_hatalar [] = '$this->tmp Bir Dizin Değil!' . $this->tmp;
            return False;
        }

        if (!is_dir($this->image_dir)) {
            $this->_hatalar [] = 'Resim Output Dizini bulunamadı!';
            return False;
        }
        if ($this->image_output == NULL) {
            $this->_hatalar [] = 'Resim Çıktı Dosyasını Belirtmemişsiniz $this->image_output!';
            return False;
        }

        if ($this->file == NULL) {
            $this->_hatalar [] = 'Ekran Görüntüsü Alınacak Dosyayı Belirtin $this->file';
            return False;
        }

        $this->image_w = $this->image_w != NULL ? $this->image_w : 250;
        $this->image_h = $this->image_h != NULL ? $this->image_h : 250;


        $cikti = $this->image_dir . $this->image_output;
        $s = $this->image_w . "x" . $this->image_h;

        $this->komut = $this->ffmpeg . "  -itsoffset -4  -i " . $this->file . " -vcodec mjpeg -vframes 1 -an -f rawvideo -s " . $s . " -y " . $cikti . " > " . $this->log . basename($this->file) . "_resim.txt 2>&1";
        shell_exec($this->komut);

        if (!file_exists($cikti) OR filesize($cikti) === 0) {
            $this->_hatalar [] = 'Resim Dosyası Oluşturulamadı.Log Dosyası:' . $this->log . basename($this->file) . '_resim.txt';
            return False;
        }

        $this->sonuc ['resim_file'] = $this->image_output;
        $this->sonuc ['resim_komut'] = $this->komut;
        file_put_contents($this->log . basename($this->file) . '_resim.txt', "\n" . $this->komut, FILE_APPEND | LOCK_EX);
        return True;
    }


    function makeMultipleTwo($value)
    {
        $sType = gettype($value / 2);
        if ($sType === "integer") {
            return $value;
        }
        return ($value - 1);
    }


}


