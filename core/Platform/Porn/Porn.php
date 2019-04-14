<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/6
 * Time: 10:50
 */
namespace core\Platform\Porn;

use core\Cache\FileCache;
use core\Common\Downloader;
use core\Config\Config;
use core\Http\Curl;
use \DOMDocument;
use \ErrorException;

class Porn extends Downloader
{
    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    /**
     * @param array $curlProxy
     * @return array|bool|null
     * @throws ErrorException
     */
    public function getVideosUrl(array $curlProxy=[]):?array
    {
        $videosUrlCache = (new FileCache())->get($this->requestUrl);
        if($videosUrlCache){
            return $videosUrlCache;
        }

        $html = Curl::get($this->requestUrl, $this->requestUrl, $curlProxy, false);

        if(empty($html[0])){
            $this->error('Error：not found html');
        }

        preg_match_all('/<div\sid="viewvideo-title">\s*(.*)\s*<\/div>/', $html[0], $matchesTitle);
        if(!isset($matchesTitle[1][0])){
            $this->error('Error：not found title');
        }

        preg_match_all('/<source\ssrc="(.*)"\stype="video\/mp4">/', $html[0], $matchesVideo);
        if(!isset($matchesVideo[1][0])){
            $this->error('Error：not found mp4 source');
        }

        $title = str_replace(PHP_EOL, '', $matchesTitle[1][0]);
        $this->setVideosTitle($title);

        $videosUrl = $matchesVideo[1][0];
        $videosInfo = [
            'url' => $videosUrl,
            'title' => $this->videosTitle,
        ];

        if(!$videosUrl){
            $this->error('Error：videos url is empty');
        }

        (new FileCache())->set($this->requestUrl, $videosInfo);

        return $videosInfo;

    }

    /**
     * @throws ErrorException
     */
    public function download():void
    {
        $httpProxy = Config::instance()->get('http_proxy');
        $curlProxy = [];
        if($httpProxy){
            $curlProxy = [
                CURLOPT_PROXY => $httpProxy,
            ];
        }

        $videosUrl = $this->getVideosUrl($curlProxy);

        $this->videosTitle = $videosUrl['title'];
        $this->downloadUrls[0] = $videosUrl['url'];

        $this->downloadFile(['totalSize' => self::DEFAULT_FILESIZE, 'list' => [self::DEFAULT_FILESIZE]], $curlProxy);
        $this->success($this->ffmpFileListTxt);
    }

}