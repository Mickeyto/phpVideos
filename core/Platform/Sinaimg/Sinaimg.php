<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/18
 * Time: 14:37
 */

namespace core\Platform\Sinaimg;


use core\Common\Downloader;

class Sinaimg extends Downloader
{
    public function __construct(string $url)
    {
        $url = str_replace(['https:', 'http:', '//'], '', $url);
        $this->requestUrl = 'https://' . $url;
    }

    public function download():void
    {
        $this->videosTitle = 'Sina-' . md5($this->requestUrl);
        $this->downloadFile($this->requestUrl, $this->videosTitle);
        $this->success();
    }

}