<?php
/* AKM Software.
 * @2010
 * Dosya Modül Sayfası
 * mod/file/index.php
 */
class file_mod extends indexMain
{

    /* public $url, $name, $dizin, $tumb, $vid;
    private $image;
    public $error;
    public $outputfile;
    public $temp_files;*/
    public $mod, $url, $name, $error;


    public $result = [
        'ext' => '',
        'url' => '',
        'size' => '',
        'type' => '',
        'filename' => '',
        'original_name' => ''

    ];

    public function index()
    {
        echo '';
    }

    public function _upload($option)
    {
        $this->result['ext'] = Req::Files_Ext($this->name);
        if (in_array($this->result['ext'], $option['valid_ext']) === FALSE) {
            $this->error = "Geçersiz Dosya Uzantısı.Yalnızca :" . implode(',', $option['valid_ext']) . " uzantılı dosyalar yüklenebilir";
            return FALSE;
        }

        $dir = root_dir . 'static' . DS . 'mod_attachment' . DS . $this->mod . DS;
        if ($this->_fix_dir($dir) === FALSE) {
            $this->error = $dir . " Oluşturulamadı!";
            return FALSE;
        }

        $this->result['url'] = $this->_generate_url($dir, 20);
        $this->result['type'] = Req::Files_Type($this->name);
        $this->result['original_name'] = Req::Files_Name($this->name);

        if (move_uploaded_file(Req::Files_Tmp($this->name), $dir . $this->result['url'])) {
            return TRUE;
        }
        $this->error = "Dosya Yüklenirken Hata Oluştu" . $_FILES['error'][$this->name];
        return FALSE;
    }


    public function _generate_url($dir, $size)
    {
        $url = Helper::randomkey($size);
        if (file_exists($dir . $url)) {
            return $this->_generate_url($dir, $size);
        }
        return $url;
    }


    public function _delete()
    {
        $file = root_dir . 'static' . DS . 'mod_attachment' . DS . $this->mod . DS . $this->url;
        if (is_file($file) && @unlink($file)) {
            return TRUE;
        }
        $this->error = $file . " Silinemedi!";
        return FALSE;
    }

    private function _fix_dir($d)
    {
        if (!file_exists($d)) {
            Load::libfile('File');
            $file = new File();
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
}
