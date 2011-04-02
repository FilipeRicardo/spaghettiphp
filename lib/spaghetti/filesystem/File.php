<?php

namespace spaghetti\filesystem;

require 'lib/spaghetti/filesystem/Exception.php';

/*
    Class: File

    Extremely basic class for file IO. It is just a OO layer on top of
    PHP's native functions.
*/
class File {
    protected $filename;
    protected $file;
    protected $mode;

    public function __construct($filename, $mode = 'r') {
        // @todo catch exceptions
        // @todo manage paths
        $this->mode = $mode;
        $this->file = fopen($filename, $this->mode);
    }

    public static function open($filename, $mode, $lambda) {
        $file = new static($filename, $mode);
        $lambda($file);
        $file->close();
    }

    public static function path($path, $dir = null) {
        if(is_null($dir)) {
            if(defined('\SPAGHETTI_ROOT')) {
                $dir = \SPAGHETTI_ROOT;
            }
            else {
                $dir = realpath(null);
            }
        }

        return static::join($dir, $path);
    }

    public static function join() {
        return implode(\DIRECTORY_SEPARATOR, func_get_args());
    }

    public function close() {
        fclose($this->file);
    }

    public function write($string) {
        return fwrite($this->file, $string);
    }

    public function read($length) {
        return fread($this->file, $length);
    }
}