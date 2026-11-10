<?php
class FileException extends Exception
{

    public function show()
    {
        $last = current($this->getTrace());
        $error = "<div class='file-exception'>";
        $error .= '<div class="file-exception-body"><pre>';
        $error .= "<h1>FileException</h1>";
        $error .= "<h4>" . htmlspecialchars($this->getMessage(), ENT_QUOTES, 'UTF-8') . "</h4>";
        $error .= "<h3>Error</h3>\n";
        $error .= "<b>File:</b>" . htmlspecialchars($this->getFile(), ENT_QUOTES, 'UTF-8') . "\n";
        $error .= "<b>Line:</b>" . (int) $this->getLine() . "\n";
        $error .= "<h3>Calling Files</h3>\n";
        $error .= "<b>File:</b>" . htmlspecialchars($last['file'] ?? '', ENT_QUOTES, 'UTF-8') . "\n";
        $error .= "<b>Line:</b>" . (int) ($last['line'] ?? 0) . "\n";
        $error .= "<b>Class:</b>" . htmlspecialchars($last['class'] ?? '', ENT_QUOTES, 'UTF-8') . "\n";
        $error .= "<b>Function:</b>" . htmlspecialchars($last['function'] ?? '', ENT_QUOTES, 'UTF-8') . "\n";
        $error .= "<h3>Trace String</h3>\n";
        $error .= htmlspecialchars($this->getTraceAsString(), ENT_QUOTES, 'UTF-8') . "\n";
        $error .= "</pre></div>";
        $error .= "</div>";
        echo $error;
    }
}

class File
{
    public function newdir($file, $chmod = 0777)
    {
        $target = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file), DIRECTORY_SEPARATOR);
        if (is_dir($target)) {
            return true;
        }

        $segments = [];
        $current = $target;
        while (!empty($current) && !is_dir($current)) {
            $segments[] = $current;
            $parent = dirname($current);
            if ($parent === $current) {
                break;
            }
            $current = $parent;
        }

        $segments = array_reverse($segments);
        foreach ($segments as $segment) {
            if (!is_dir($segment)) {
                if (!@mkdir($segment, $chmod) && !is_dir($segment)) {
                    throw new FileException("Directory could not be created");
                }
                @chmod($segment, $chmod);
            }
        }

        if (is_dir($target)) {
            return true;
        }

        throw new FileException("Directory could not be created");
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