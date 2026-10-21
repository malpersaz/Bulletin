<?php
/**
 * Amed Framework
 * User: AKM
 * Date: 5/2/11 - 4:54 PM
 * Copyright: AKM Software
 * E-Mail: info@akmsoftware.co.uk
 * Web: www.akmsoftware.co.uk
 * Class: AmedResize
 * File: AmedResize.class.php
 * Description: Image resizing utility library
 */
class AmedResizeException extends Exception
{

    public function show()
    {
        echo "<br>";
        echo $this->getMessage();

        echo "<br>
        File:";
        echo $this->getFile();
        echo "<br>
        Line:";
        echo $this->getLine();
        echo "<br>
        TraceAsString:<br><pre>";
        echo $this->getTraceAsString();
        echo "</pre>";
    }
}

class AmedResize
{

    public $image_source;
    public $image_file_source;
    public $image_width;
    public $image_height;
    public $image_resized;
    public $ext;
    public $valid_ext = ['jpg', 'jpeg', 'png', 'gif'];


    public function getfile($file = NULL)
    {
        try {
            $this->_getfile($file);
        } catch (AmedResizeException $e) {
            echo $e->show();
            return FALSE;
        }
        return TRUE;
    }

    public function getformfile($filename)
    {
        try {
            $this->_getformfile($filename);
        } catch (AmedResizeException $e) {
            echo $e->show();
            return FALSE;
        }
        return TRUE;
    }

    public function resize($width = NULL, $height = NULL, $option = 'auto')
    {

        $result = $this->getoption($width, $height, $option);


        $optiomalwidth = $result['width'];
        $optiomalheight = $result['height'];


        $this->image_resized = imagecreatetruecolor($optiomalwidth, $optiomalheight);
        // http://www.php.net/manual/en/function.imagecolortransparent.php#89927
        $transparencyIndex = imagecolortransparent($this->image_source);
        $transparencyColor = ['red' => 255, 'green' => 255, 'blue' => 255];

        if ($transparencyIndex >= 0) {
            $transparencyColor = imagecolorsforindex($this->image_source, $transparencyIndex);
        }

        $transparencyIndex = imagecolorallocate($this->image_resized, $transparencyColor['red'], $transparencyColor['green'], $transparencyColor['blue']);
        imagefill($this->image_resized, 0, 0, $transparencyIndex);
        imagecolortransparent($this->image_resized, $transparencyIndex);
        // Preserve transparency
        imagecopyresampled($this->image_resized, $this->image_source, 0, 0, 0, 0, $optiomalwidth, $optiomalheight, $this->image_width, $this->image_height);
        return $this;
    }

    /*
    * Process $_FILES data submitted from form
    * @param $filename string
    * @param $index NULL|integer
    * @return getsource function
    */
    public function _getformfile($filename, $index = NULL)
    {

        if ($index !== NULL) {
            if ($_FILES[$filename] === NULL || isset($_FILES[$filename]['tmp_name'][$index]) === FALSE || isset($_FILES[$filename]['name'][$index]) === FALSE) {
                throw new AmedResizeException("\$_FILES[$filename]['tmp_name'] is empty, FILES ERROR:" . $_FILES[$filename]['error'][$index]);
            }
            $this->ext = $this->_ext($_FILES[$filename]['name'][$index]);
            if (!in_array($this->ext, $this->valid_ext)) {
                throw new AmedResizeException(htmlspecialchars($_FILES[$filename]['name'][$index]) . " invalid file extenstion.");
            }
            return $this->getsource($_FILES[$filename]['name'][$index]);
        }
        if ($_FILES[$filename] === NULL || isset($_FILES[$filename]['tmp_name']) === FALSE || isset($_FILES[$filename]['name']) === FALSE) {
            throw new AmedResizeException("\$_FILES[$filename]['tmp_name'] is empty, FILES ERROR:" . $_FILES[$filename]['error']);
        }
        $this->ext = $this->_ext($_FILES[$filename]['name']);
        if (!in_array($this->ext, $this->valid_ext)) {
            throw new AmedResizeException(htmlspecialchars($_FILES[$filename]['name']) . " invalid file extenstion.");
        }
        return $this->getsource($_FILES[$filename]['tmp_name']);
    }

    public function _getfile($file = NULL)
    {
        if ($file === NULL) {
            throw new AmedResizeException("_getfile method not allowed NULL");
        }
        if (is_file($file) === FALSE) {
            throw new AmedResizeException(htmlspecialchars($file) . " is Not File");
        }
        $this->ext = $this->_ext($file);
        if (!in_array($this->ext, $this->valid_ext)) {
            throw new AmedResizeException(htmlspecialchars($file) . " invalid file extenstion.");
        }
        return $this->getsource($file);
    }

    public function getsource($file)
    {

        $this->image_file_source = @file_get_contents($file);

        if ($this->image_file_source === FALSE) {
            throw new AmedResizeException(htmlspecialchars($file) . " The file could not be loaded.");
        }


        $this->image_source = @imagecreatefromstring($this->image_file_source);
        if ($this->image_source === FALSE) {
            throw new AmedResizeException(htmlspecialchars($file) . " File has an invalid type or extension.");
        }

        $this->image_width = imagesx($this->image_source);
        $this->image_height = imagesy($this->image_source);
    }

    /*
     * Get Extenstion
     * @param file mixed
     * @return mixed
     */
    private function _ext($file)
    {
        $a = explode(".", $file);
        return strtolower(end($a));
    }

    public function save($savefile, $quality = "100")
    {
        if ($this->image_resized === FALSE) {
            return FALSE;
        }

        $extension = '.' . $this->ext;

        switch ($extension) {
            case '.jpg' :
            case '.jpeg' :
                if ((imagetypes() & IMG_JPG) !== 0) {
                    imagejpeg($this->image_resized, $savefile, $quality);
                }
                break;

            case '.gif' :
                if ((imagetypes() & IMG_GIF) !== 0) {
                    imagegif($this->image_resized, $savefile);
                }
                break;

            case '.png' :
                // *** Scale quality from 0-100 to 0-9
                $scaleQuality = round(($quality / 100) * 9);


                // *** Invert quality setting as 0 is best, not 9
                $invertScaleQuality = 9 - $scaleQuality;
                if ($invertScaleQuality == 0) {
                    $invertScaleQuality = 1;
                }
                /*
                    echo "\$scaleQuality:".$scaleQuality;
                    echo "-";
                    echo "\$invertScaleQuality:".$invertScaleQuality;*/

                if ((imagetypes() & IMG_PNG) !== 0) {
                    imagepng($this->image_resized, $savefile, $invertScaleQuality);
                }
                break;

            default :
                // *** No extension - No save.
                return FALSE;
                break;
        }

        $this->image_resized = null;
        return TRUE;
    }

    /* getOption method
    * Calculates width and height based on provided option parameter
    * @param width NULL|integer
    * @param height NULL|integer
    * @param option auto,width,height,original,extract
    */
    public function getoption($width = NULL, $height = NULL, $option = 'original')
    {
        $result = ['width' => $this->image_width, 'height' => $this->image_height];
        if ($option == 'original') {
            $result = $this->_original_resize();
        } elseif ($option == 'width') {
            $result = $this->_width_resize($width);
        } elseif ($option == 'height') {
            $result = $this->_height_resize($height);
        } elseif ($option == 'auto') {
            $result = $this->_auto_resize($width, $height);
        } elseif ($option == 'extract') {
            $result = $this->_extract_resize($width, $height);
        } elseif ($width !== NULL && $height === NULL) {
            $result = $this->_width_resize($width);
        } elseif ($width === NULL && $height !== NULL) {
            $result = $this->_height_resize($width);
        } else {
            $result = $this->_original_resize();
        }
        return $result;
    }


    private function _auto_resize($width = NULL, $height = NULL)
    {
        // Return original dimensions if both width and height are NULL
        if ($width === NULL && $height === NULL) {
            return $this->_original_resize();
        }
        // Resize by height if image height exceeds target and width is unspecified or smaller than original
        if (($this->image_height > $height) && ($width === NULL || $this->image_width < $width)) {
            return $this->_height_resize($height);
        }
        // Resize by width if image width exceeds target and height is unspecified or smaller than original
        if (($this->image_width > $width) && ($height === NULL || $this->image_height < $height)) {
            return $this->_width_resize($width);
        }
        // Keep original dimensions if both target dimensions are larger than original image
        if (($this->image_height < $height) && ($this->image_width < $width)) {
            return $this->_original_resize($width);
        }
        // Exact dimension calculation fallback
        return $this->_extract_resize($width, $height);
    }

    private function _original_resize()
    {
        return ['width' => $this->image_width, 'height' => $this->image_height];
    }

    private function _width_resize($width = NULL)
    {
        $result['width'] = $this->image_width <= $width ? $this->image_width : $width;
        $ratio = $result['width'] / $this->image_width;
        $result['height'] = $this->image_height * $ratio;
        return $result;
    }

    private function _height_resize($height = NULL)
    {
        $result['height'] = $this->image_height <= $height || $height === NULL ? $this->image_height : $height;
        $ratio = $result['height'] / $this->image_height;
        $result['width'] = $this->image_width * $ratio;
        return $result;
    }

    private function _extract_resize($width = NULL, $height = NULL)
    {
        return ['width' => $width === NULL ? $this->image_width : $width, 'height' => $height === NULL ? $this->image_height : $height];
    }

    public function flush()
    {
        unset($this->image_file_source);
        unset($this->image_resized);
        unset($this->image_source);
        unset($_FILES);
    }


}

