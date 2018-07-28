<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/5
 * Time: 15:40
 */
namespace core\Platform\Douyu;

use core\Cache\FileCache;
use core\Common\Downloader;
use core\Common\FFmpeg;
use core\Common\M3u8;
use core\Http\Curl;

class Douyu extends Downloader
{
    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    /**
     * PC：https://v.douyu.com/api/swf/getStreamUrl POST params:[did,tt（时间）,sign（未知加密）,vid]
     *
     * @return string
     * @throws \ErrorException
     */
    public function getDid():string
    {
        $defaultDid = '10000000000000000000000000001501';
        //client_id=1|6
        $didApiUrl = 'https://passport.douyu.com/lapi/did/api/get?client_id=1';

        $didCache = (new FileCache())->get($didApiUrl);
        if($didCache){
            return $didCache;
        }

        $getInfo = Curl::get($didApiUrl, 'https://v.douyu.com', [], false);
        if($getInfo){
            $json = json_decode($getInfo[0], true);
            if(!isset($json['data']['did'])){
                throw new \ErrorException('Could not get did');
            }

            $defaultDid = $json['data']['did'];
            (new FileCache())->set($didApiUrl, $defaultDid, 7200);
        }

        return $defaultDid;
    }

    /**
     * @param string $vid
     * @return string
     * @throws \ErrorException
     */
    public function getMobileInfo(string $vid):string
    {
        $getInfoUrl = 'https://vmobile.douyu.com/video/getInfo?vid=' . $vid;
        $videoUrl = (new FileCache())->get($getInfoUrl);

        if(!$videoUrl){
            $getInfo = Curl::get($getInfoUrl, 'https://v.douyu.com/show/'.$vid);
            if(!$getInfo){
                $this->error('Errors：not found url info');
            }

            if($getInfo[0]){
                $json = json_decode($getInfo[0], true);
                $videoUrl = $json['data']['video_url'];
                if(empty($videoUrl)){
                    $this->error('Errors：not found video url');
                }

                (new FileCache())->set($getInfoUrl, $videoUrl, 7200);
            }
        }

        return $videoUrl;
    }

    /**
     * @param string $vid
     * @return array|null|string
     * @throws \ErrorException
     */
    public function getVideoTitle(string $vid):?string
    {
        $pcInfoUrl = 'https://v.douyu.com/video/video/getVideoUrl?vid=' . $vid;

        $videoTitle = (new FileCache())->get($pcInfoUrl);
        if(!$videoTitle){
            $jsonInfo = Curl::get($pcInfoUrl, 'https://vswf.douyucdn.cn/player/vplayer.swf?ver=v6.671');

            if(!$jsonInfo){
                $this->error('Errors：get pc video info');
            }

            if(!$jsonInfo[0]){
                $this->error('Errors：json empty');
            }

            $json = json_decode($jsonInfo[0], true);
            if($json['error'] != 0){
                $this->error('Errors：'.$json['error']);
            }

            $videoTitle = $json['data']['title'];
            (new FileCache())->set($pcInfoUrl, $videoTitle, 7200);
        }


        return $videoTitle;
    }

    /**
     * @param string $url
     * @return string
     * @throws \ErrorException
     */
    public function getM3u8(string $url):string
    {
        $m3u8Info = (new FileCache())->get($this->requestUrl.'m3u8');

        if(!$m3u8Info){
            $playListInfo = Curl::get($url, $this->requestUrl);
            if(!$playListInfo){
                $this->error('Errors：get m3u8 list');
            }
            if(empty($playListInfo[0])){
                $this->error('Errors：m3u8 is empty');
            }
            $m3u8Info = $playListInfo[0];

            (new FileCache())->set($this->requestUrl.'m3u8', $m3u8Info);
        }

        return $m3u8Info;
    }

    /**
     * @throws \ErrorException
     */
    public function download():void
    {
        $urlInfo = parse_url($this->requestUrl, PHP_URL_PATH);
        $urlArray = explode('show/', $urlInfo);
        if(!isset($urlArray[1])){
            $this->error('not found vid');
        }
        $vid = $urlArray[1];

        $videosTitle = $this->getVideoTitle($vid);

        $videoUrl = $this->getMobileInfo($vid);

        $playerHost = pathinfo($videoUrl, PHP_URL_PATH);
        $contents = $this->getM3u8($videoUrl);

        $this->setVideosTitle($videosTitle);

        $mUrls = M3u8::getUrls($contents);

        if(!$mUrls){
            $this->error('Errors：m3u8 urls empty');
        }

        $urls = [];
        $playerHost = str_replace('http', 'https', $playerHost);
        foreach($mUrls as $val){
            $pa = trim($val);
            $url =  $playerHost . '/' .$pa;
            array_push($urls, $url);
        }

        $this->downloadUrls = $urls;

        $downloadFileInfo = $this->downloadFile();
        if($downloadFileInfo < 1024){
            printf("\n\e[41m%s\033[0m\n", 'Errors：download file 0');
        } else {
            FFmpeg::concatToMp4($this->videosTitle, $this->ffmpFileListTxt, './Videos/');
        }

        $this->deleteTempSaveFiles();
        $this->success($this->ffmpFileListTxt);
    }

}