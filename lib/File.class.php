<?php
class FileException extends Exception
{

    public function show()
    {
        $last = current($this->getTrace());
        $error = "
          <style>
            .dexception {
                background: #FFFFCC;
                font-size: 14px;
                color: #000000;
            }
            .dexception h4 {
                padding: 8px;
                background: #eee;
                font: 'Courier New', Courier, mono;
                font-weight: bold;
            }
            .dexception h3{
                margin-bottom:2px;
            }
            .e {
                font-size: 12px;
                padding: 2px 8px;

                font: 'Courier New', Courier, mono;
            }

            .e_red {
                color: red;
                font-weight: bold;
            }
        </style>";
        $error .= "<div class='dexception'>";
        $error .= '<div class="e"><pre>';
        $error .= "<h1>FileException</h1>";
        $error .= "<h4>" . $this->getMessage() . "</h4>";
        $error .= "<h3>Error</h3>\n";
        $error .= "<b class='e_red'>File:</b>" . $this->getFile() . "\n";
        $error .= "<b class='e_red'>Line:</b>" . $this->getLine() . "\n";
        $error .= "<h3>Calling Files</h3>\n";
        $error .= "<b class='e_red'>File:</b>" . $last['file'] . "\n";
        $error .= "<b class='e_red'>Line:</b>" . $last['line'] . "\n";
        $error .= "<b class='e_red'>Class:</b>" . $last['class'] . "\n";
        $error .= "<b class='e_red'>Function:</b>" . $last['function'] . "\n";
        $error .= "<b class='e_red'>Args:</b>" . print_r($last['args'], TRUE) . "\n";
        $error .= "<h3>Trac String</h3>\n";
        $error .= $this->getTraceAsString() . "\n";
        $error .= "</pre></div>";
        $error .= "</div>";
        echo $error;
    }
}

class File
{
    public function newdir($file, $chmod = 0777)
    {
        if (file_exists($file)) {
            return TRUE;
        }
        mkdir($file,$chmod,true);
        /*
        $dir = '';
        $d = explode(DS, $file);
        foreach ($d AS $a) {
            $dir = $dir . DS . $a;
            if (!file_exists($dir)) {
                if (mkdir($dir, $chmod)) {
                    continue;
                } else {
                    throw new FileException($dir . " directory is not created!");
                }
            }
        }
*/
        if (file_exists($file)) {
            return TRUE;
        }
        throw new FileException($file . " directory is not created!");
    }

    public function newfile($file)
    {
        if (fclose(fopen($file, 'a+'))) {
            return True;
        }
        throw new FileException($file . " file is not created!");
    }

    public function deletefile($file)
    {
        if (unlink($file)) {
            return True;
        }
        throw new FileException($file . " file is not deleted!");
    }

    public function deletedir($file)
    {
        if ($this->recursiveDelete($file) === True) {
            return True;
        }
        throw new FileException($file . " directory is not deleted!");
    }

    function recursiveDelete($str)
    {
        if (!file_exists($str)) {
            return True;
        }
        if (is_file($str)) {
            return $this->deletefile($str);
        }
        if (is_dir($str)) {
            $scan = glob(rtrim($str, '/') . '/*');
            foreach ($scan as $path) {
                $this->recursiveDelete($path);
            }
            return rmdir($str);
        }
        return False;
    }

    public function writefile($file,$content){
        if(file_put_contents($file,$content)){
            return TRUE;
        }
        throw new FileException($file." is not writable");
    }

}