<?php

namespace App\Http\Controllers\Disk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FileReader extends Controller
{

    protected $handler = null;

    protected $fbuffer = [];
 
    public function __construct($filename) {

        if (!($this->handler = fopen($filename, "rb")))
            throw new \Exception("Cannot open the file");

    }
 
    public function read($count_line = 10) {

        if (!$this->handler)
            throw new \Exception("Invalid file pointer");
 
        while(!feof($this->handler)) {

            $this->fbuffer[] = fgets($this->handler);
            $count_line--;
            
            if ($count_line == 0)
                break;

        }
 
        return $this->fbuffer;

    }
 
    public function setOffset($line = 0) {

        if (!$this->handler)
            throw new \Exception("Invalid file pointer");
 
        while(!feof($this->handler) && $line--) {
             fgets($this->handler);
        }

    }

}
