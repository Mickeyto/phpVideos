<?php
namespace core\Platform\Haokan;

use core\Cache\FileCache;
use core\Common\Downloader;
use core\Common\FFmpeg;
use core\Common\M3u8;
use core\Http\Curl;
use ErrorException;

class Haokan extends Downloader
{
    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    /**
     * @param null $argvOpt
     * @throws ErrorException
     */
    public function download($argvOpt=null):void
    {
        $videosTitle = trim($this->videosTitle);

        $this->setVideosTitle($videosTitle);

        $mUrls = $this->requestUrl;

        if(!$mUrls){
            $this->error('Errors：m3u8 urls empty');
        }

        $urls = [];
        array_push($urls, $mUrls);

        $this->downloadUrls = $urls;
        $this->playlist = $urls;

        //show playlist
        if(isset($argvOpt['i'])){
            $this->outPlaylist();
        }

        $downloadFileInfo = $this->downloadFile();
        if($downloadFileInfo < 1024){
            printf("\n\e[41m%s\033[0m\n", 'Errors：download file 0');
        }

        $this->success($this->ffmpFileListTxt);
    }

}