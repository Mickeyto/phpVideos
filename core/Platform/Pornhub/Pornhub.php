<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/5
 * Time: 15:41
 */
namespace core\Platform\Pornhub;

use core\Cache\FileCache;
use core\Common\ArrayHelper;
use core\Common\Downloader;
use core\Config\Config;
use core\Http\Curl;

class Pornhub extends Downloader
{
    public $pornhubHtmlFile = './pornhub.html';

    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    /**
     * @return bool|null|string
     * @throws \ErrorException
     */
    public function getVideosJson():?string
    {
        $videosJsonCache = (new FileCache())->get($this->requestUrl);
        if($videosJsonCache){
            return $videosJsonCache;
        }
        //PHP cUrl 不做代理 ，直接走本地
        exec("curl {$this->requestUrl} > pornhub.html", $curlHtml);

        $htmlErrors = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTMLFile($this->pornhubHtmlFile);
        $player = $dom->getElementById('player');
        $videosId = $player->getAttribute('data-video-id');

        libxml_use_internal_errors($htmlErrors);

        $javaScript = $player->nodeValue;

        if(!$videosId){
            throw new \ErrorException('无法解析该视频');
        }

        $patter = "/flashvars_{$videosId} = (.*?)};/is";
        preg_match_all($patter, $javaScript, $matches);


        if(!isset($matches[1][0])){
            throw new \ErrorException('无法解析该视频真实地址');
        }

        unset($dom);
        unlink($this->pornhubHtmlFile);
        (new FileCache())->set($this->requestUrl, $matches[1][0] . '}');

        return $matches[1][0] . '}';
    }

    /**
     * @param $videosJson
     * @return array
     */
    public function getVideosList($videosJson):array
    {
        $videosLists = json_decode($videosJson, true);

        $this->setVideosTitle($videosLists['video_title']);

        $videosList = array_filter($videosLists['mediaDefinitions'], function($var){
            if(!empty($var['videoUrl'])){
                return $var;
            }
        });

        return $videosList;
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

        $videosJson = $this->getVideosJson();
        $videosList = $this->getVideosList($videosJson);

        if(!$videosList){
            echo PHP_EOL . 'No video found'. PHP_EOL;
            exit(0);
        }

        $videosList = ArrayHelper::multisort($videosList, 'quality', SORT_DESC);

        $this->videoQuality = $videosList[0]['quality'];
        $this->downloadUrls[0] = $videosList[0]['videoUrl'];

        $fileSizeArray = [
            'totalSize' => self::DEFAULT_FILESIZE,
            'list' => [self::DEFAULT_FILESIZE],
        ];

        $this->downloadFile($fileSizeArray, $curlProxy);
        $this->success($this->ffmpFileListTxt);
    }
}