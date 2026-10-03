<?php
/* AKM Software.
 * @2010
 * Resim Modül Sayfası
 * mod/image/index.php
 */
// include composer autoload
// require root_dir . 'vendor/autoload.php';

// import the Intervention Image Manager Class
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;

class image_mod extends indexMain
{

    public $rmodel;
    public $m;
    public $image_model;
    public $url, $name, $dizin, $tumb, $vid;
    private $image;
    public $error;
    public $outputfile;
    public $temp_files;
    public $mod;
    public $custom_name = null;
    public $valid_ext = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'ico'];


    public function index()
    {
        $option = [
            '/' =>
            [
                'width' => NULL,
                'height' => NULL,
            ],
            '/thumb' =>
            [
                'width' => 150,
                'height' => NULL,
                'option' => 'width',
            ],
            '/thumb2' =>
            [
                'width' => 250,
                'height' => NULL,
                'option' => 'width',
            ],
        ];
        $directories = array_keys($option);
        print_r($option);
        print_r($directories);
    }

    public function _resize($option)
    {

        $driver = extension_loaded('gd') ? new GdDriver() : new ImagickDriver();
        $manager = new ImageManager($driver);

        $file = $_FILES[$this->name];
        $ext = strtolower(Helper::extension($file['name']));
        
        // Force WebP for supported formats
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
             $target_ext = 'webp';
        } else {
             $target_ext = $ext;
        }
        
        $url = $this->_generate_url($target_ext);


        if (!in_array($ext, $this->valid_ext)) {
            throw new Exception("invalid_extension|" . $ext);
        }


        foreach ($option as $k => $v) {

            $dir = $k === '/' ? root_dir . 'static' . DS . $this->mod . DS : root_dir . 'static' . DS . $this->mod . $k . DS;
            $this->_fix_dir($dir);
            /*var_dump($dir);
            var_dump($url);
            print_r($file);*/
            if ($ext === 'svg' || $ext === 'ico') {

                file_put_contents($dir . $url, file_get_contents($file['tmp_name']));
                //move_uploaded_file($dir . $url, $file['tmp_name']);
                continue;
            }


            $image = $manager->read($file['tmp_name']);
            $quality = isset($v['quality']) ? (int) $v['quality'] : 80;
            if ($quality <= 0 || $quality > 100) {
                $quality = 80;
            }

            if (isset($v['option'])) {
                // Calculate target dimensions
                $targetWidth = isset($v['width']) && $v['width'] !== null ? (int) $v['width'] : null;
                $targetHeight = isset($v['height']) && $v['height'] !== null ? (int) $v['height'] : null;
                $optionType = (string) $v['option'];

                if ($optionType === 'cover' || $optionType === 'crop') {
                    if ($targetWidth && $targetHeight) {
                        $image->cover($targetWidth, $targetHeight);
                    } elseif ($targetWidth) {
                        $image->scale(width: $targetWidth);
                    } elseif ($targetHeight) {
                        $image->scale(height: $targetHeight);
                    }
                } elseif ($optionType === 'coverDown') {
                    if ($targetWidth && $targetHeight) {
                        $image->coverDown($targetWidth, $targetHeight);
                    } elseif ($targetWidth) {
                        $image->scaleDown(width: $targetWidth);
                    } elseif ($targetHeight) {
                        $image->scaleDown(height: $targetHeight);
                    }
                } elseif ($optionType === 'width') {
                    if ($targetWidth && $image->width() > $targetWidth) {
                        $calcHeight = (int) round($image->height() * ($targetWidth / $image->width()));
                        $image->resize($targetWidth, $calcHeight);
                    }
                } elseif ($optionType === 'height') {
                    if ($targetHeight && $image->height() > $targetHeight) {
                        $calcWidth = (int) round($image->width() * ($targetHeight / $image->height()));
                        $image->resize($calcWidth, $targetHeight);
                    }
                } elseif ($optionType === 'original') {
                    // Retain original dimensions
                } else {
                    if ($targetWidth && $targetHeight) {
                        $image->resize($targetWidth, $targetHeight);
                    }
                }

                // Create a new transparent canvas with the target dimensions
                // This ensures we have a clean alpha channel
                $canvas = $manager->create($image->width(), $image->height());
                $canvas->place($image);
                $image = $canvas;

            } else {
                if ($v['width'] != NULL || $v['height'] != NULL) {
                    $targetWidth = $v['width'];
                    $targetHeight = $v['height'];
                    
                    if ($targetWidth === NULL) {
                        $targetWidth = (int) round($image->width() * ($targetHeight / $image->height()));
                    }
                    if ($targetHeight === NULL) {
                        $targetHeight = (int) round($image->height() * ($targetWidth / $image->width()));
                    }
                    
                    if ($image->width() > $targetWidth || $image->height() > $targetHeight) {
                         $image->resize($targetWidth, $targetHeight);
                         
                         // Apply same canvas logic for this case too
                         $canvas = $manager->create($image->width(), $image->height());
                         $canvas->place($image);
                         $image = $canvas;
                    }
                }
            }

            // Preserve format and transparency
            $success = false;
            
            if ($target_ext === 'webp') {
                $success = $image->toWebp($quality)->save($dir . $url);
            } elseif ($ext === 'png') {
                $success = $image->toPng()->save($dir . $url);
            } elseif ($ext === 'gif') {
                $success = $image->toGif()->save($dir . $url);
            } else {
                $success = $image->save($dir . $url);
            }

            if ($success === false) {
                @unlink($dir . $url);
                $this->error = 'File could not be saved to: ' . $dir;
                return false;
            }
        }

        $this->url = $url;
        return true;
    }


    public function _rollback()
    {
        $filelib = Load::lib('File');
        if (count($this->temp_files) > 0) {
            foreach ($this->temp_files as $file) {
                $filelib->deletefile($file);
            }
        }
    }

    public function _generate_url($ext)
    {
        // Keep original format to avoid WebP compatibility issues
        $final_ext = strtolower($ext);
        if (!in_array($final_ext, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'ico', 'webp'])) {
            $final_ext = 'jpg'; // Default fallback
        }
        if ($this->custom_name) {
            return $this->custom_name . "." . $final_ext;
        }
        $url = Helper::randomkey(15) . "." . $final_ext;
        $file = root_dir . $this->mod . DS . $url;
        if (file_exists($file)) {
            return $this->_generate_url($ext);
        }
        return $url;
    }


    public function _delete_files($option)
    {
        $directories = array_keys($option);
        $count = count($directories) - 1;
        for ($i = 0; $i <= $count; $i++) {
            // Dosyanın Atılacağı Dizin
            $dirkey = $directories[$i];

            // Eğer $dirkey / ise Sonuna / atıp // yapmamak için if
            if ($dirkey === '/') {
                $dir = root_dir . 'static' . DS . $this->mod . $dirkey;
            } else {
                $dir = root_dir . 'static' . DS . $this->mod . $dirkey . DS;
            }
            $this->outputfile = $dir . $this->url;
            if (file_exists($this->outputfile)) {
                if (@unlink($this->outputfile)) {
                    continue;
                }
                $this->error = 'File isn\'t Delete :' . $this->outputfile;
                return FALSE;
            }
        }
        return TRUE;
    }


    //Tekli Resim
    public function _thumb($en = NULL, $boy = NULL, $option = 'width')
    {
        // Amed Resize Kütüphanesini Çalıştıralım
        $amedresize = Load::lib('AmedResize');
        if ($amedresize->getformfile($this->name)) {
            // Modülün Dizinleri Yoksa Oluşturalım.
            $this->_fix();
            // Yeni Dosya Adı
            $this->url = $this->vid . Helper::randomkey(5) . "." . $amedresize->ext;
            if ($amedresize->resize($en, $boy, $option)->save($this->dizin . $this->url)) {
                $amedresize->flush();
                return TRUE;
            }
            $amedresize->flush();
            return FALSE;
        }
        return FALSE;
    }

    //Biri Orjinal Resim 1 TUMB
    public function _orjinal_tumb($dizi)
    {
        $k_en = isset($dizi['kucuk']['0']) ? $dizi['kucuk']['0'] : '150';
        $k_boy = isset($dizi['kucuk']['1']) ? $dizi['kucuk']['1'] : NULL;
        // Modülün Dizinleri Yoksa Oluşturalım.
        $this->_fix();
        $amedresize = Load::lib('AmedResize');
        if ($amedresize->getformfile($this->name)) {
            $this->url = $this->vid . Helper::randomkey(4) . "." . $amedresize->ext;
            $tumb = $amedresize->resize($k_en, $k_boy, 'width')->save($this->tumb . $this->url);
            $orginal = $amedresize->resize()->save($this->dizin . $this->url);
            if (
                $tumb
                and
                $orginal
            ) {
                $amedresize->flush();
                return TRUE;
            }
            echo "Tumb:" . var_dump($tumb);
            echo "\n";
            echo "Original:" . var_dump($orginal);
            if (file_exists($this->tumb . $this->url)) {
                @unlink($this->tumb . $this->url);
            }
            if (file_exists($this->dizin . $this->url)) {
                @unlink($this->dizin . $this->url);
            }
            $amedresize->flush();
            return FALSE;
        }
        return True;
    }

    //Tek Resim 2 TUMB
    public function _multi_tumb($dizi)
    {
        $b_en = isset($dizi['buyuk']['0']) ? $dizi['buyuk']['0'] : '250';
        $b_boy = isset($dizi['buyuk']['1']) ? $dizi['buyuk']['1'] : NULL;
        $k_en = isset($dizi['kucuk']['0']) ? $dizi['kucuk']['0'] : '150';
        $k_boy = isset($dizi['kucuk']['1']) ? $dizi['kucuk']['1'] : NULL;
        $amedresize = Load::lib('AmedResize');
        if ($amedresize->getformfile($this->name)) {
            // Modülün Dizinleri Yoksa Oluşturalım.
            $this->_fix();
            $this->url = $this->vid . Helper::randomkey(4) . "." . $amedresize->ext;
            $orginal = $amedresize->resize($b_en, $b_boy, 'width')->save($this->dizin . $this->url);
            $tumb = $amedresize->resize($k_en, $k_boy, 'width')->save($this->tumb . $this->url);
            if (
                $orginal
                and
                $tumb
            ) {
                $amedresize->flush();
                return TRUE;
            }
            echo "Tumb:" . var_dump($tumb);
            echo "\n";
            echo "Original:" . var_dump($orginal);
            if (file_exists($this->tumb . $this->url)) {
                @unlink($this->tumb . $this->url);
            }
            if (file_exists($this->dizin . $this->url)) {
                @unlink($this->dizin . $this->url);
            }
            $amedresize->flush();
            return FALSE;
        }
        return FALSE;
    }

    /*
     * Bir Modülün Ana Resmi Silinince Diğer Resimlerden Birini Ana Resim yapar
     */
    public function _mod_fix()
    {
        $this->_fix();
        $this->model = Load::model($this->mod);
        $this->model->Getid($this->vid);

        if ($this->model->image != '' && file_exists($this->dizin . $this->model->image)) {
            return True;
        }
        $this->db = Load::lib('Database');
        $this->db->table('image');
        $this->db->where(['vid' => $this->vid, 'mod' => $this->mod]);
        $this->db->getResult(TRUE, TRUE)->toArray();
        $p = $this->db->getSize();
        $first = $this->db->first();
        $this->model->image = $p > 0 ? $first['url'] : '';

        if ($this->model->update('id')) {
            return True;
        }
        exit($this->model->db->sql_error());
        return True;
    }

    public function _mod_update()
    {
        $this->_fix();
        $this->db = Load::lib('Database');
        $data['tablo'] = 'image';
        $data['where'] = ['vid' => $this->vid, 'mod' => $this->mod];
        $this->db->select($data);
        $this->db->fetch_object_one($this->db->sql_text);
        $this->rmodel = $this->db->result;
        $this->m = Load::model($this->mod);
        $this->m->Getid($this->vid);
        $this->db->count($data);
        $p = $this->db->result;
        $this->m->image = $p > 0 ? $this->rmodel->url : '';
        if ($this->m->update('id')) {
            return True;
        }
        exit($this->m->db->sql_error());
        return True;
    }

    // DB'ye Ekle
    private function _add()
    {
        $this->image_model = Load::model('image');
        $this->image_model->mod = $this->mod;
        $this->image_model->url = $this->url;
        $this->image_model->vid = $this->vid;
        $this->image_model->created_at = date('Y-m-d H:i:s');
        if ($this->rmodel->save()) {
            return TRUE;
        }
        return FALSE;
    }

    // Eğer Static Dizininde Modül Adından Klasor ve tumb klasoru yoksa oluştur.
    private function _fix()
    {
        $this->dizin = root_dir . 'static' . DS . $this->mod . DS;
        $this->tumb = root_dir . 'static' . DS . $this->mod . DS . 'tumb' . DS;
        if (!file_exists($this->dizin)) {
            $file = Load::lib('Dosya');
            $file->yeniklasor($this->dizin);
        }
        if (!file_exists($this->tumb)) {
            $file = Load::lib('Dosya');
            $file->yeniklasor($this->tumb);
        }
    }

    private function _fix_dir($d)
    {
        if (!file_exists($d)) {
            $file = Load::lib('File');
            try {
                $file->newdir($d);
                return TRUE;
            } catch (FileException $e) {
                echo $e->show();
                return FALSE;
            }
        }
        return TRUE;
    }

    public function _vid_delete($s, $m)
    {
        $this->model = Load::model('image');
        $this->model->db->fetch_array("SELECT
		* FROM image WHERE mod='" . $m . "' AND vid='" . $s . "'");
        if (is_array($this->model->db->result)) {
            foreach ($this->model->db->result as $r) {
                if ($this->_dosyasil($r['mod'], $r['url'])) {
                    if ($this->model->delete_id($r['id'])) {
                        continue;
                    }
                    break;
                }
            }
        }
    }

    private function _dosyasil($mod, $name)
    {
        if (unlink(root_dir . 'static' . DS . $mod . DS . $name) and unlink(root_dir . 'static' . DS . $mod . DS . 'tumb' . DS . $name)) {
            return True;
        }

        return False;
    }

    // Silme Fonksiyonu
    public function _delete_records($sil = NULL)
    {
        // image Modeli Yükleyelim
        $this->model = Load::model('image');
        if (is_array($sil)) {
            foreach ($sil as $s) {
                $this->model->Getid($s);
                if ($this->model->delete_id($s)) {
                    continue;
                }
                echo $this->model->db->sql_error();
            }
            return True;
        }
        if ($this->model->delete_id($sil)) {
            return True;
        }
        echo $this->model->db->sql_error();
    }
}
