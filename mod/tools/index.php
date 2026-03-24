<?php

class tools_mod extends indexMain
{

    public $session;
    // İçeriği Kaydet
    public function saveText($mod_name = NULL, $vid = NULL)
    {

        // Modül varmı ve Gallery Ekleniyormu
        $gmod = Load::model('mod_gallery_mod');
        if ($gmod->exists_mod_name($mod_name) === FALSE) {
            print(":)");
            return FALSE;
        }

        if ($vid === NULL || $mod_name === NULL) {
            exit("::)");
        }

        $mod_name = Req::Url_Clear($mod_name);
        $vid = Req::Url_Clear($vid);


        $content = Load::model($mod_name);
        $content->where_id_and_status($vid, 'y');
        $content->gRo();
        if ($content->getSize() == 0) {
            exit(':::)');
        }
        $content->sync();

        if (isset($content->acl_id) && Run::import('index', '_permission_kontrol', [$content->acl_id])) {
            return;
        }


        $content->content = str_replace("<br>", "\n", Req::unhtmlentities($content->content));
        $sonuc = $content->name . "\n";
        if (isset($content->created_at)) {
            $sonuc .= "\n" . date('h:i d.m.Y', strtotime($content->created_at)) . "\n" . "\n";
        }


        $sonuc .= $content->content;
        if (ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }

        header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: private", false); // required for certain browsers
        header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Content-Disposition: attachment; filename=\"" . basename(Req::unhtmlentities($content->name)) . ".txt\";");
        header('Content-Transfer-Encoding: binary');
        header("Content-Type:application/octet-stream");
        echo strip_tags($sonuc);
    }

    public function saveHtml($mod_name = NULL, $vid = NULL)
    {

        // Modül varmı ve Gallery Ekleniyormu
        $gmod = Load::model('mod_gallery_mod');
        if ($gmod->exists_mod_name($mod_name) === FALSE) {
            print(":)");
            return FALSE;
        }

        if ($vid === NULL || $mod_name === NULL) {
            exit("::)");
        }

        $mod_name = Req::Url_Clear($mod_name);
        $vid = Req::Url_Clear($vid);


        $content = Load::model($mod_name);
        $content->where_id_and_status($vid, 'y');
        $content->gRo();
        if ($content->getSize() == 0) {
            exit(':::)');
        }
        $content->sync();

        if (isset($content->acl_id) && Run::import('index', '_permission_kontrol', [$content->acl_id])) {
            return;
        }


        $content->content = Req::unhtmlentities($content->content);
        $sonuc = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>
      ' . $content->name . '
    </title></head><body><h1>' . $content->name . '</h1>';


        //$sonuc = "<h1>".$content->name . "</h1>";
        if (isset($content->created_at)) {
            $sonuc .= "<br><i>" . date('h:i d.m.Y', strtotime($content->created_at)) . "</i><br>";
        }


        $sonuc .= $content->content;
        $sonuc .= '</body></html>';
        if (ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }

        header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: private", false); // required for certain browsers
        header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Content-Disposition: attachment; filename=\"" . basename(Req::unhtmlentities($content->name)) . ".html\";");
        header('Content-Transfer-Encoding: binary');
        header("Content-Type:application/octet-stream");
        echo $sonuc;
    }

    // Gallery Resmini Kaydet
    public function gallery_image_save($mod_name = NULL, $vid = NULL, $id = NULL, $show = NULL)
    {
        // Modül varmı ve Gallery Ekleniyormu
        $gmod = Load::model('mod_gallery_mod');
        if ($gmod->exists_mod_name($mod_name) === FALSE) {
            print(":)");
            return FALSE;
        }

        if ($id === NULL || $vid === NULL || $mod_name === NULL) {
            exit("::)");
        }

        $mod_name = Req::Url_Clear($mod_name);
        $vid = Req::Url_Clear($vid);
        $id = Req::Url_Clear($id);


        $content = Load::model($mod_name);
        $content->where_id_and_status($vid, 'y');
        $content->gRo();
        if ($content->getSize() == 0) {
            exit(':::)');
        }
        $content->sync();

        if (isset($content->acl_id) && Run::import('index', '_permission_kontrol', [$content->acl_id])) {
            return;
        }


        $gallery = Load::model('mod_gallery');
        $gallery->where_mod_name_and_vid_and_id($mod_name, $vid, $id);
        $gallery->gRo();
        // Galler Resmi Yokmuş
        if ($gallery->getSize() === 0) {
            exit('::::)');
        }

        $gallery->sync();


        $image = root_dir . 'static' . DS . 'mod_gallery' . DS . $mod_name . DS . 'original' . DS . basename($gallery->url);

        if (!file_exists($image)) {
            exit(':::::)');
        }

        if ($show !== NULL) {
            header("Content-Type:image/jpeg");
            readfile($image);
            exit();
        }

        if (ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }
        header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: private", false); // required for certain browsers
        header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Content-Disposition: attachment; filename=\"" . basename(Helper::sefurl($content->name)) . "-" . $gallery->id . "." . Helper::extension($gallery->url) . "\";");
        header('Content-Transfer-Encoding: binary');
        header("Content-Type:image/jpeg");
        readfile($image);
    }

    // Resim +Text Hepsini Kaydet
    public function saveAll($mod_name = NULL, $vid = NULL)
    {

        define('cache_zip', cache_dir . 'zip' . DS);
        // Modül varmı ve Gallery Ekleniyormu
        $gmod = Load::model('mod_gallery_mod');
        if ($gmod->exists_mod_name($mod_name) === FALSE) {
            print(":)");
            return FALSE;
        }

        if ($vid === NULL || $mod_name === NULL) {
            exit("::)");
        }

        $mod_name = Req::Url_Clear($mod_name);
        $vid = Req::Url_Clear($vid);

        $content = Load::model($mod_name);
        $content->where_id_and_status($vid, 'y');
        $content->gRo();
        if ($content->getSize() == 0) {
            exit(':::)');
        }
        $content->sync();

        if (isset($content->acl_id) && Run::import('index', '_permission_kontrol', [$content->acl_id])) {
            return;
        }


        $gallery = Load::model('mod_gallery');
        $gallery->where_mod_name_and_vid($mod_name, $vid);
        $gallery->gR();
        // Galler Resmi Yokmuş
        if ($gallery->getSize() === 0) {
            exit('::::)');
        }

        $content->content = str_replace("<br>", "\n", Req::unhtmlentities($content->content));
        $sonuc = $content->name . "\n" . "\n" . date('h:i d.m.Y', strtotime($content->created_at)) . "\n" . "\n";
        $sonuc .= strip_tags($content->content);


        $zip = new ZipArchive();
        $file = Helper::sefurl($content->name) . ".zip";

        $ac = $zip->open(cache_zip . $file, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
        if ($ac !== TRUE) {
            exit("<$file> açılamadı\n");
        }
        $zip->addFromString(Req::unhtmlentities($content->name) . ".txt", "$sonuc\n");

        $i = 1;
        foreach ($gallery->toArray() as $r) {
            $image = root_dir . 'static' . DS . 'mod_gallery' . DS . $mod_name . DS . 'original' . DS . basename($r['url']);
            if (file_exists($image)) {
                $zip->addFile($image, basename(Helper::sefurl($content->name)) . "-" . $i . "." . Helper::extension($r['url']));
            }
            unset($image);
            $i++;
        }


        $zip->close();

        if (!file_exists(cache_zip . $file)) {
            exit('Dosya Oluşturulamadı!');
        }

        /*
          if (ini_get('zlib.output_compression'))
          ini_set('zlib.output_compression', 'Off');
          header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
          header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
          header("Cache-Control: private", false); // required for certain browsers
          header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
          header("Pragma: no-cache");
          header("Content-Disposition: attachment; filename=\"" . basename($file) . "\";");
          header('Content-Transfer-Encoding: binary');
          header("Content-Type:application/zip"); */

        header("Content-Type: application/octet-stream");
        header("Cache-Control: public");
        header("Content-Disposition: attachment; filename=\"" . basename($file) . "\";");
        header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");


        readfile(cache_zip . $file);
        unlink(cache_zip . $file);
    }

    public function saveFile($mod_name = NULL, $vid = NULL, $id = NULL)
    {

        if ($vid === NULL || $mod_name === NULL || $id === NULL) {
            exit("::)");
        }

        // Modül varmı ve Dosya Ekleniyormu
        $dmod = Load::model('mod_attachment_mod');
        if ($dmod->exists_mod_name($mod_name) === FALSE) {
            print(":)");
            return FALSE;
        }

        $dmod->sync();


        $mod_name = Req::Url_Clear($mod_name);
        $vid = Req::Url_Clear($vid);
        $id = Req::Url_Clear($id);


        $content = Load::model($mod_name);

        if (isset($content->status)) {
            $content->where_id_and_status($vid, 'y');
        } else {
            $content->where_id($vid);
        }
        $content->gRo();

        if ($content->getSize() == 0) {
            exit(':::)');
        }
        $content->sync();

        if (isset($content->acl_id) && Run::import('index', '_permission_kontrol', [$content->acl_id])) {
            return;
        }


        $file = Load::model('mod_attachment');
        $file->where_mod_name_and_vid_and_id($mod_name, $vid, $id);
        $file->gRo();
        // Dosya Yokmuş
        if ($file->getSize() === 0) {
            exit('::::)');
        }
        $file->sync();

        header("Content-Type: " . $file->type);
        header("Cache-Control: public");
        header("Content-Disposition: attachment; filename=\"" . basename($file->name) . "\";");
        header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        readfile(root_dir . 'static' . DS . 'mod_attachment' . DS . $mod_name . DS . $file->url);
    }


    public function videoResim($mod_name = NULL, $vid = NULL, $id = NULL)
    {
        $this->session = Load::lib('Session');
        /*if(Roll::hasPermission($mod_name,'video_download')===FALSE){
            exit('.');
        }*/

        if ($vid === NULL || $mod_name === NULL || $id === NULL) {
            exit("1");
        }

        // Modül varmı ve Gallery Ekleniyormu
        $gmod = Load::model('mod_gallery_mod');
        if ($gmod->exists_mod_name($mod_name) === FALSE) {
            print("2");
            return FALSE;
        }



        $mod_name = Req::Url_Clear($mod_name);
        $vid = Req::Url_Clear($vid);
        $id = Req::Url_Clear($id);


        $content = Load::model($mod_name);
        $content->where_id_and_status($vid, 'y');
        $content->gRo();
        if ($content->getSize() == 0) {
            exit('3');
        }
        $content->sync();

        if (isset($content->acl_id) && Run::import('index', '_permission_kontrol', [$content->acl_id])) {
            exit('4');
        }
        if ($content->video == '') {
            exit('5');
        }

        $videos = unserialize($content->video);

        if (is_array($videos) == FALSE) {
            exit('6');
        }

        $video = Load::model('mod_video');
        $video->where_id($id);
        $video->gRo();
        // Video Yokmuş
        if ($video->getSize() === 0) {
            exit('7');
        }
        $video->sync();
        header("Content-Type:image/jpeg");
        $file = root_dir . 'static' . DS . 'mod_video' . DS . 'image' . DS . $video->image;
        if (file_exists($file) == FALSE || is_file($file) == FALSE) {
            exit('8');
        }
        readfile($file);
    }

    public function videoFlv($mod_name = NULL, $vid = NULL, $id = NULL)
    {
        $this->session = Load::lib('Session');
        /*if(Roll::hasPermission($mod_name,'video_download')===FALSE){
            exit('.');
        }*/
        if ($vid === NULL || $mod_name === NULL || $id === NULL) {
            exit("1");
        }
        // Modül varmı ve Gallery Ekleniyormu
        $gmod = Load::model('mod_gallery_mod');
        if ($gmod->exists_mod_name($mod_name) === FALSE) {
            print("2");
            return FALSE;
        }



        $mod_name = Req::Url_Clear($mod_name);
        $vid = Req::Url_Clear($vid);
        $id = Req::Url_Clear($id);

        $content = Load::model($mod_name);
        $content->where_id_and_status($vid, 'y');
        $content->gRo();
        if ($content->getSize() == 0) {
            exit('3');
        }
        $content->sync();

        if (isset($content->acl_id) && Run::import('index', '_permission_kontrol', [$content->acl_id])) {
            exit('4');
        }
        if ($content->video == '') {
            exit('5');
        }

        $videos = unserialize($content->video);

        if (is_array($videos) == FALSE) {
            exit('6');
        }

        $video = Load::model('mod_video');
        $video->where_id($id);
        $video->gRo();
        // Video Yokmuş
        if ($video->getSize() === 0) {
            exit('7');
        }
        $video->sync();

        $file = root_dir . 'static' . DS . 'mod_video' . DS . 'flv' . DS . $video->flv;
        if (file_exists($file) == FALSE || is_file($file) == FALSE) {
            exit('8');
        }
        Load::libfile('VideoStream');
        $stream = new VideoStream($file);
        $stream->start();
    }



    public function saveVideo($mod_name = NULL, $vid = NULL, $id = NULL)
    {

        $this->session = Load::lib('Session');
        if (Roll::hasPermission($mod_name, 'video_download') === FALSE) {
            exit('.');
        }
        if ($vid === NULL || $mod_name === NULL || $id === NULL) {
            exit("1");
        }

        // Modül varmı ve Gallery Ekleniyormu
        $gmod = Load::model('mod_gallery_mod');
        if ($gmod->exists_mod_name($mod_name) === FALSE) {
            print("2");
            return FALSE;
        }



        $mod_name = Req::Url_Clear($mod_name);
        $vid = Req::Url_Clear($vid);
        $id = Req::Url_Clear($id);

        //$this->session=Load::lib('Session');
        $content = Load::model($mod_name);
        $content->where_id_and_status($vid, 'y');
        $content->gRo();
        if ($content->getSize() == 0) {
            exit('3');
        }
        $content->sync();

        if (isset($content->acl_id) && Run::import('index', '_permission_kontrol', [$content->acl_id])) {
            exit('4');
        }
        if ($content->video == '') {
            exit('5');
        }

        $videos = unserialize($content->video);

        if (is_array($videos) == FALSE) {
            exit('6');
        }

        $video = Load::model('mod_video');
        $video->where_id($id);
        $video->gRo();
        // Video Yokmuş
        if ($video->getSize() === 0) {
            exit('7');
        }
        $video->sync();
        $file = root_dir . 'static' . DS . 'mod_video' . DS . 'original' . DS . $video->original;
        if (file_exists($file) == FALSE || is_file($file) == FALSE) {
            exit('8');
        }
        if (ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }
        header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: private", false); // required for certain browsers
        header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Content-Disposition: attachment; filename=\"" . basename($video->original) . "\";");
        header('Content-Transfer-Encoding: binary');
        header("Content-Type:application/octet-stream");
        $handle = @fopen($file, "r");
        if ($handle) {
            while (($buffer = fgets($handle, 4096)) !== false) {
                echo $buffer;
            }
            /*if (!feof($handle)) {
                echo "Error: unexpected fgets() fail\n";
            }*/
            fclose($handle);
        }
    }
}
