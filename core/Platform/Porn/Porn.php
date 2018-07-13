<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/6
 * Time: 10:50
 */
namespace core\Platform\Porn;

use core\Common\Downloader;

class Porn extends Downloader
{
    public $url = '';
    public function __construct($url)
    {
        $this->url = $url;
    }

    public function download()
    {
        $this->downloadFile($this->url, 'porn.mp4');
    }

}