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
use \ErrorException;

class Pornhub extends Downloader
{
    public $pornhubHtmlFile = './pornhub.html';

    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    /**
     * @param array $curlOptions
     * @return bool|null|string
     * @throws ErrorException
     */
    public function getVideosJson(?array $curlOptions=[]):?string
    {
        $videosJsonCache = (new FileCache())->get($this->requestUrl);
        if($videosJsonCache){
            return $videosJsonCache;
        }

        $html = Curl::get($this->requestUrl, 'https://www.pornhub.com/', $curlOptions);
        if(empty($html[0])){
            $this->error('request pornhub error');
        }

        preg_match_all('/<div\sid="player"\sclass="original\smainPlayerDiv"\sdata-video-id="(.*)">/', $html[0], $matchesVid);

        if(!isset($matchesVid[1][0])){
            $this->error('无法解析该视频');
        }

        $patter = "/flashvars_{$matchesVid[1][0]} = (.*?)};/is";
        preg_match_all($patter, $html[0], $matches);

        if(!isset($matches[1][0])){
            $this->error('无法解析该视频真实地址');
        }

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

        $videosJson = $this->getVideosJson($curlProxy);
        $videosList = $this->getVideosList($videosJson);

        if(!$videosList){
            echo PHP_EOL . 'No video found'. PHP_EOL;
            exit(0);
        }

        $videosList = ArrayHelper::multisort($videosList, 'quality', SORT_DESC);
        $ind = 0;
        if(is_array($videosList[$ind]['quality'])){
            $ind += 1;
        }

        $this->videoQuality = $videosList[$ind]['quality'];
        $this->downloadUrls[0] = $videosList[$ind]['videoUrl'];

        $fileSizeArray = [
            'totalSize' => self::DEFAULT_FILESIZE,
            'list' => [self::DEFAULT_FILESIZE],
        ];

        $this->downloadFile($fileSizeArray, $curlProxy);
        $this->success($this->ffmpFileListTxt);
    }
}