<?php

class Gather
{
    private $destination;

    private $source;

    private $force_over;

    public function __construct($destination, $source, $force_over = true)
    {
        $this->destination = $destination;
        $this->source = $source;
        $this->force_over = $force_over;
    }

    public function run()
    {
        if(!is_dir($this->destination)) {
            $this->writeLog('destination is not a validabled directory');
            exit;
        }
        if(!is_dir($this->source)) {
            $this->writeLog('source is not a validabled directory');
            exit;
        }
        $this->destination = rtrim($this->destination, DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR;
        $this->source = rtrim($this->source, DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR;
        $this->digFiles($this->source);
        $this->writeLog('completed');
    }

    private function digFiles($dir)
    {
        $dir_list = $this->filterDir(scandir($dir));
        foreach($dir_list as $_dir) {
            $full_path = $dir. $_dir;
            if(is_file($full_path)) {
                $this->moveFile($full_path, $this->destination. $_dir, $dir);
            } else {
                $this->digFiles($full_path. DIRECTORY_SEPARATOR);
            }
        }
    }

    private function moveFile($source, $dst, $source_dir = '')
    {
        if($this->force_over === false) {
            $ds = DIRECTORY_SEPARATOR;
            if(is_file($dst)) {
                $segments = array_filter(explode($ds, $source_dir));
                $updir = array_pop($segments);
                $segments = array_filter(explode($ds, $dst));
                $cur_file = array_pop($segments);
                $cur_file = $updir. '_'. $cur_file;
                $dst = implode($ds, $segments). $ds. $cur_file;
            }
        }
        if(copy($source, $dst)) {
            $this->writeLog('copy '. $source. ' to '. $dst);
        } else {
            $this->writeLog('failed on copying '. $source. ' to '. $dst);
        }
    }

    private function filterDir($dir_list)
    {
        if(!is_array($dir_list)) {
            return [];
        }
        return array_filter($dir_list, function($item) {
            return !in_array($item, ['.', '..']);
        });
    }

    private function writeLog($msg)
    {
        echo $msg;
        echo PHP_EOL;
    }
}

$dest = __DIR__;
$opts = getopt('f:ht:');
if(isset($opts['h'])) {
    echo 'Gather files from the specified directory.'. PHP_EOL;
    echo 'Usage:'. PHP_EOL;
    echo "\t". '-f the source directory'. PHP_EOL;
    echo "\t". '-t [optional] the destination directory. default is the current directory.'. PHP_EOL;
    echo "\t". 'Example: copy all files in /tmp/source/ to /home/user/dest/'. PHP_EOL;
    echo "\t\t". 'php gather.php -f /tmp/source/ -t /home/user/dest/';
    exit;
}
if(isset($opts['t'])) {
    $dest = $opts['t'];
}
$source = @$opts['f'];
if(empty($source)) {
    exit('-f source must be specified');
}
$app = new Gather($dest, $source, false);
$app->run();
