<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2019-03-01
 * Time: 14:35
 */
namespace core\Platform\Toutiaoimg;

use core\Cache\FileCache;
use core\Common\ArrayHelper;
use core\Common\Downloader;
use core\Http\Curl;

class Toutiaoimg extends Downloader
{
    public $referer = '';
    public $locationHref = 'https://www.365yg.com/a';
    public $vid = '';
    public $jsonp = 'axiosJsonpCallback1';


    public function __construct(string $url)
    {
        $this->requestUrl = $url;
        $this->initVid();
    }

    /**
     *
     */
    public function initVid():void
    {
        preg_match_all('/group\/(.*?)\??\//', $this->requestUrl, $matches);

        $this->vid = isset($matches[1][0]) ? $matches[1][0] : '';
    }

    /**
     * 生成随机数，要小于1
     * @return string
     */
    public function generateR():string
    {
        $avgRand = mt_rand() / mt_getrandmax();
        return ltrim($avgRand, '0.');
    }

    /**
     * 字符串循环冗余检验
     * @param string $str
     * @return int
     */
    public function generateS(string $str):int
    {
        $s = crc32($str);

        return $s;
    }

    /**
     * 时间戳
     * @return int
     */
    public function getMicrotime():int
    {
        $microTime = microtime(true);
        $seconds = round($microTime, 3);

        return $seconds;
    }

    /**
     * @return string
     * @throws \ErrorException
     */
    public function matchVideoIdAndTitle():string
    {
        if(empty($this->vid)){
            $this->error('vid is empty');
        }

        $locationUrl = $this->locationHref . $this->vid;
        $response = Curl::get($locationUrl, $locationUrl);

        if(empty($response[0])){
            $this->error('location url error');
        }

        preg_match_all('/videoId:\s?\'(.*)\'/i', $response[0], $matches);
        preg_match('/<title>(.*?)<\/title>/i', $response[0], $matchesTitle);

        if(!isset($matches[1][0])){
            $this->error('videoId matches error');
        }
        if(isset($matchesTitle[1])){
            $this->setVideosTitle($matchesTitle[1]);
        } else {
            $this->setVideosTitle('toutiao-' . $this->vid);
        }

        return $matches[1][0];
    }

    /**
     * 头条 javascript CRC32 算法
     */
    public function javascriptCrc32()
    {
        $javascript = <<<JAVASCRIPT
            var h = 'https://ib.365yg.com/video/urls/v/1/toutiao/mp4/003d89b422634630b48a60452c058c2a';

var r = h +
'?r=' +
Math.random().toString(10).substring(2);
var n = (function () {
  for (var t = 0, e = new Array(256), n = 0; 256 !== n; ++n) (t = 1 & (t = 1 & (t = 1 & (t = 1 & (t = 1 & (t = 1 & (t = 1 & (t = 1 & (t = n)
  ? - 306674912 ^ (t >>> 1) 
  : t >>> 1)
  ? - 306674912 ^ (t >>> 1) 
  : t >>> 1)
  ? - 306674912 ^ (t >>> 1) 
  : t >>> 1)
  ? - 306674912 ^ (t >>> 1) 
  : t >>> 1)
  ? - 306674912 ^ (t >>> 1) 
  : t >>> 1)
  ? - 306674912 ^ (t >>> 1) 
  : t >>> 1)
  ? - 306674912 ^ (t >>> 1) 
  : t >>> 1)
  ? - 306674912 ^ (t >>> 1) 
  : t >>> 1),
  (e[n] = t);
  return 'undefined' != typeof Int32Array
  ? new Int32Array(e) 
  : e;
}) ();
var i = (function (t) {
  for (var e, r, i = - 1, o = 0, a = t.length; o < a; ) (e = t.charCodeAt(o++)) < 128
  ? (i = (i >>> 8) ^ n[255 & (i ^ e)]) 
  : e < 2048
  ? (i = ((i = (i >>> 8) ^ n[255 & (i ^ (192 | ((e >> 6) & 31)))]) >>>
  8) ^ n[255 & (i ^ (128 | (63 & e)))]) 
  : e >= 55296 && e < 57344
  ? ((e = 64 + (1023 & e)), (r = 1023 & t.charCodeAt(o++)), (i = ((i = ((i = ((i = (i >>> 8) ^ n[255 & (i ^ (240 | ((e >> 8) & 7)))]) >>>
  8) ^ n[255 & (i ^ (128 | ((e >> 2) & 63)))]) >>>
  8) ^ n[
  255 & (i ^ (128 | ((r >> 6) & 15) | ((3 & e) << 4)))
  ]) >>>
  8) ^ n[255 & (i ^ (128 | (63 & r)))])) 
  : (i = ((i = ((i = (i >>> 8) ^ n[255 & (i ^ (224 | ((e >> 12) & 15)))]) >>>
  8) ^ n[255 & (i ^ (128 | ((e >> 6) & 63)))]) >>>
  8) ^ n[255 & (i ^ (128 | (63 & e)))]);
  return - 1 ^ i;
}) (r) >>> 0;

JAVASCRIPT;

    }

    /**
     * @note https://ib.365yg.com/video/urls/v/1/toutiao/mp4/003d89b422634630b48a60452c058c2a?r=3861589717048173&s=1591039197&aid=1364&vfrom=xgplayer&callback=axiosJsonpCallback1&_=1551432390783
     * @param string $vid
     * @return array|mixed|string|null
     * @throws \ErrorException
     */
    public function videoDetail(string $vid)
    {
        $cacheKey = 'toutiaoimg-' . $vid;
        $urlCache = (new FileCache())->get($cacheKey);
        if($urlCache){
            return $urlCache;
        }

        $r = $this->generateR();
        $_s = $this->getMicrotime();

        $apiUrl = 'https://ib.365yg.com';
        $locationPathname = '/video/urls/v/1/toutiao/mp4/'. $vid .'?r=' . $r;

        $s = $this->generateS($locationPathname);
        $apiUrl .= $locationPathname .'&s='. $s .'&aid=1364&vfrom=xgplayer&callback=' . $this->jsonp .'&_=' . $_s;

        $response = Curl::get($apiUrl, $this->referer);

        if(empty($response[0])){
            $this->error('request api error');
        }

        $callback = ltrim($response[0], $this->jsonp . '(');
        $callback = rtrim($callback, ')');
        $json = json_decode($callback, true);

        $newArray = ArrayHelper::multisort($json['data']['video_list'], 'vwidth', SORT_DESC);

        (new FileCache())->set($cacheKey, $newArray, 300);

        return $newArray;
    }

    /**
     * @throws \ErrorException
     */
    public function download(): void
    {
        $videoId = $this->matchVideoIdAndTitle();
        $videoDetail = $this->videoDetail($videoId);
        $urlTotal = count($videoDetail);

        $arrayKey = 'video_'.$urlTotal;
        $videosInfo = $videoDetail[$arrayKey];
        $videoUrl = base64_decode($videosInfo['main_url']);

        $this->videoQuality = $videosInfo['definition'];
        $this->downloadUrls = [$videoUrl];
        $fileSizeArray = [
            'totalSize' => $videosInfo['size'],
            'list' => 1024,
        ];

        $this->fileExt = '.mp4';
        $header = [
            CURLOPT_REFERER => $this->locationHref,
        ];
        $downloadFileInfo = $this->downloadFile($fileSizeArray, $header);

        if($downloadFileInfo < 1024){
            printf("\n\e[41m%s\033[0m\n", 'Errors：download file 0');
        }

        $this->success($this->ffmpFileListTxt);
    }

}