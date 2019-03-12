<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/5
 * Time: 15:41
 */
namespace core\Platform\Xvideos;

use core\Cache\FileCache;
use core\Common\Downloader;
use core\Common\FFmpeg;
use core\Common\M3u8;
use core\Config\Config;
use core\Http\Curl;

class Xvideos extends Downloader
{
    /**
     * Xvideos constructor.
     * @param string $url
     */
    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    /**
     * @param $videoId
     * @param array $curlProxy
     * @return array|bool|null
     * @throws \ErrorException
     */
    public function getVideosInfo(string $videoId, array $curlProxy=[]):?array
    {
        $jsonUrl = 'https://www.xvideos.com/html5player/getvideo/'. $videoId .'/2';

        $urlCache = (new FileCache())->get($jsonUrl);
        if(!$urlCache){
            $getJson = Curl::get($jsonUrl, $jsonUrl, $curlProxy);
            if($getJson){
                $json = json_decode($getJson[0], true);
                if(false === $json['exist']){
                    throw new \ErrorException('获取json 失败');
                }
                if(!empty($json['mp4_high'])){
                    $urlCache = [
                        'url' => $json['mp4_high'],
                        'type' => 'mp4_high',
                    ];
                } elseif (!empty($json['mp4_low'])){
                    $urlCache = [
                        'url' => $json['mp4_low'],
                        'type' => 'mp4_low',
                    ];
                } else {
                    $this->error('未找到视频地址：'.$jsonUrl);
                }
            }

            (new FileCache())->set($jsonUrl, $urlCache);
        }

        return $urlCache;
    }

    /**
     * @param array $curlProxy
     * @return array|bool|null
     * @throws \ErrorException
     */
    public function matchMp4(array $curlProxy=[]):?array
    {
        $videosUrlCache = (new FileCache())->get($this->requestUrl);
        if($videosUrlCache){
            return $videosUrlCache;
        }

        $html = Curl::get($this->requestUrl, $this->requestUrl, $curlProxy, false);

        if(!$html || empty($html[0])){
            $this->error('Error：not found html');
        }

        preg_match('/<title>(.*?)<\/title>/', $html[0], $matchesTitle);
        preg_match_all('/setVideoUrl[Low|High][^\')]+\(\'(.*?)\'\)/', $html[0], $matches);

        if(!isset($matches[1])){
            $this->error('match mp4 error');
        }

        $videosInfo = [
            'title' => $matchesTitle[1],
            'info' => [
                [
                    'type' => '3gp',
                    'url' > $matches[1][0]
                ],
                [
                    'type' => 'mp4',
                    'url' => $matches[1][1],
                ]
            ]
        ];

        (new FileCache())->set($this->requestUrl, $videosInfo);

        return $videosInfo;
    }

    /**
     * @param array $curlProxy
     * @return array|bool|null
     * @throws \ErrorException
     */
    public function matchM3u8(array $curlProxy=[]):?array
    {
        $videosUrlCache = (new FileCache())->get($this->requestUrl);
        if($videosUrlCache){
            return $videosUrlCache;
        }

        $html = Curl::get($this->requestUrl, $this->requestUrl, $curlProxy, false);

        if(!$html || empty($html[0])){
            $this->error('Error：not found html');
        }

        preg_match('/<title>(.*?)<\/title>/', $html[0], $matchesTitle);
        preg_match('/setVideoHLS\(\'(.*?)\'\)/', $html[0], $matches);

        if(!isset($matches[1])){
            $this->error('match hls error');
        }

        $domain = pathinfo($matches[1], PATHINFO_DIRNAME) . '/';

        $response = Curl::get($matches[1], $this->requestUrl, $curlProxy, false);

        if(!$html || empty($response[0])){
            $this->error('Error：not found m3u8');
        }

        $m3u8Type = M3u8::getUrls($response[0]);

        rsort($m3u8Type, SORT_NATURAL);

        $type = trim($m3u8Type[0]);
        $url = $domain . $type;
        $response = Curl::get($url, $this->requestUrl, $curlProxy, false);

        if(empty($response[0])){
            $this->error('Errors：m3u8Urls error');
        }

        $m3u8Urls = M3u8::getUrls($response[0]);

        $tsUrl = [];
        foreach($m3u8Urls as $row){
            $tempUrl = $domain . ltrim($row);
            array_push($tsUrl, $tempUrl);
        }

        $json = [
            'title' => $matchesTitle[1],
            'type' => $type,
            'url' => $tsUrl,
            'm3u8urlType' => $m3u8Type
        ];
        (new FileCache())->set($this->requestUrl, $json);

        return $json;
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

//        $videosInfo = $this->matchMp4($curlProxy);    //mp4
        $videosInfo = $this->matchM3u8($curlProxy);

        $httpProxy = Config::instance()->get('http_proxy');
        $curlProxy = [];
        if($httpProxy){
            $curlProxy = [
                CURLOPT_PROXY => $httpProxy,
            ];
        }

        $this->downloadUrls = $videosInfo['url'];
        $this->setVideosTitle($videosInfo['title']);

        $this->videoQuality = $videosInfo['type'];

        $this->fileExt = '.ts';
        $downloadFileInfo = $this->downloadFile([], $curlProxy);

        if($downloadFileInfo < 1024){
            printf("\n\e[41m%s\033[0m\n", 'Errors：download file 0');
        } else {
            FFmpeg::concatToMp4($this->videosTitle, $this->ffmpFileListTxt, './Videos/');
        }

        $this->deleteTempSaveFiles();
        $this->success($this->ffmpFileListTxt);

    }

}