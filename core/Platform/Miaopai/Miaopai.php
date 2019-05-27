<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/5
 * Time: 15:40
 */
namespace core\Platform\Miaopai;

use core\Cache\FileCache;
use core\Common\Downloader;
use core\Http\Curl;
use \ErrorException;

class Miaopai extends Downloader
{
    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    /**
     * @param string $smid
     * @return array
     * @throws ErrorException
     */
    public function getVideoInfo(string $smid):array
    {
        $apiUrl = 'https://n.miaopai.com/api/aj_media/info.json?smid='. $smid .'&appid=530&_cb=_jsonpoom96huif8r';

        $res = Curl::get($apiUrl, $this->requestUrl);
        if(empty($res[0])){
            $this->error('request json error');
        }

        preg_match_all('/_jsonpoom96huif8r\((.*)\);/i', $res[0], $matches);
        if(!isset($matches[1]) && !is_array($matches[1])){
            $this->error('无法解析该地址');
        }

        $json = json_decode($matches[1][0], true);
        if(200 != $json['code']){
            $this->error("error code：{$json['code']}");
        }

        $description = $json['data']['description'];
        if(count($json['data']['meta_data']) < 1){
            $this->error('not found meta_data');
        }

        $videoInfo = $json['data']['meta_data'][0]['play_urls'];

        return [
            'play_urls' => $videoInfo,
            'description' => $description
        ];
    }

    /**
     * @param null $argvOpt
     * @throws ErrorException
     */
    public function download($argvOpt=null):void
    {
        $parseUrlPath = parse_url($this->requestUrl, PHP_URL_PATH);
        $pathInfoExtension = pathinfo($parseUrlPath, PATHINFO_EXTENSION);

        if($pathInfoExtension == 'mp4'){
            $this->videosTitle = 'MP-' . md5($this->requestUrl);

            $this->downloadUrls[0] = $this->requestUrl;
            $this->playlist = [$this->requestUrl];

            $this->outputVideosTitle();
            $this->downloadFile();
            $this->success($this->ffmpFileListTxt);
            exit(0);
        }

        $fileName = pathinfo($parseUrlPath, PATHINFO_FILENAME);
        $videoInfo = $this->getVideoInfo($fileName);

        $playUrls = $videoInfo['play_urls'];
        unset($playUrls['json']);
        $videosUrl = array_shift($playUrls);

        $this->playlist = $playUrls;
        $this->setVideosTitle($videoInfo['description']);

        //show playlist
        if(isset($argvOpt['i'])){
            $this->outPlaylist();
        }

        $gotoN = 1;
        gotoVideosDownload:
        $this->downloadUrls[0] = $videosUrl;
        $vi = $this->downloadFile(); //下载

        if($vi['fileSize'] == 1024 && $gotoN < 2){
            $gotoN++;
            goto gotoVideosDownload;
        }

        $this->success($this->ffmpFileListTxt);
    }

}