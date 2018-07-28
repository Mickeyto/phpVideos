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
     * @throws \ErrorException
     */
    public function download():void
    {
        $urlInfo = parse_url($this->requestUrl, PHP_URL_PATH);
        $urlInfo = explode('/', $urlInfo);

        $videoId = substr($urlInfo[1], 5);

        $httpProxy = Config::instance()->get('http_proxy');
        $curlProxy = [];
        if($httpProxy){
            $curlProxy = [
                CURLOPT_PROXY => $httpProxy,
            ];
        }

        $this->videosTitle = $urlInfo[2];
        $videosInfo = $this->getVideosInfo($videoId, $curlProxy);

        $this->videoQuality = $videosInfo['type'];
        $this->downloadUrls[0] = $videosInfo['url'];

        $this->downloadFile([], $curlProxy);
        $this->success($this->ffmpFileListTxt);
    }

}