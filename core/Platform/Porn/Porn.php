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

class Porn extends Downloader
{
    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    /**
     * @param array $curlProxy
     * @return array|bool|null
     * @throws \ErrorException
     */
    public function getVideosUrl(array $curlProxy=[]):?array
    {
        $videosUrlCache = (new FileCache())->get($this->requestUrl);
        if($videosUrlCache){
            return $videosUrlCache;
        }

        $html = Curl::get($this->requestUrl, $this->requestUrl, $curlProxy, false);

        if(!$html || empty($html[0])){
            throw new \ErrorException('该地址解析失败');
        }

        $libxmlErrors = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html[0]);
        $vidSource = $dom->getElementById('vid');

        if(empty($vidSource)){
            throw new \ErrorException('该地址解析失败');
        }

        $title = $dom->getElementsByTagName('title')->item(0)->nodeValue;

        $this->setVideosTitle($title);

        libxml_use_internal_errors($libxmlErrors);

        $videosUrl = $vidSource->getElementsByTagName('source')->item(0)->getAttribute('src');

        $videosInfo = [
            'url' => $videosUrl,
            'title' => $this->videosTitle,
        ];

        if(!$videosUrl){
            throw new \ErrorException('该地址解析失败');
        }

        (new FileCache())->set($this->requestUrl, $videosInfo);

        return $videosInfo;

    }

    /**
     * @throws \ErrorException
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

        $this->downloadFile([], $curlProxy);
        $this->success($this->ffmpFileListTxt);
    }

}