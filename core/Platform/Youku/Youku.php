<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/5
 * Time: 15:41
 */
namespace core\Platform\Youku;

use core\Common\ArrayHelper;
use core\Common\Downloader;
use core\Common\FFmpeg;
use core\Http\Curl;
use core\Cache\FileCache;

class Youku extends Downloader
{
    public $vid = '';

    public function __construct($url='')
    {
        $this->requestUrl = $url;
    }

    public function getClientTs():float
    {
        $microTime = microtime(true);
        $time = round($microTime, 3);
        return $time;
    }

    /**
     * @return string
     * @throws \ErrorException
     */
    public function getUtid():string
    {
        $utidCache = (new FileCache())->get('youkuEtag');

        if($utidCache){
            $utid = $utidCache;
        } else {
            $logEgJs = file_get_contents('https://log.mmstat.com/eg.js');

            //goldlog.Etag="字符"
            $rule = '/Etag="(.+?)"/';
            preg_match_all($rule, $logEgJs, $matches);
            if(!$matches){
                throw new \ErrorException('must be utid');
            }

            $utid = urlencode($matches[1][0]);
            (new FileCache())->set('youkuEtag', $utid, 1800);
        }

        return $utid;
    }

    public function getCKey():string
    {
        $ckey = 'DIl58SLFxFNndSV1GFNnMQVYkx1PP5tKe1siZu/86PR1u/Wh1Ptd+WOZsHHWxysSfAOhNJpdVWsdVJNsfJ8Sxd8WKVvNfAS8aS8fAOzYARzPyPc3JvtnPHjTdKfESTdnuTW6ZPvk2pNDh4uFzotgdMEFkzQ5wZVXl2Pf1/Y6hLK0OnCNxBj3+nb0v72gZ6b0td+WOZsHHWxysSo/0y9D2K42SaB8Y/+aD2K42SaB8Y/+ahU+WOZsHcrxysooUeND';

        return urlencode($ckey);
    }

    public function getVid():?string
    {
        if(empty($this->requestUrl)){
            return '';
        }
        $rule  = '/id_(.+?).html/';
        preg_match_all($rule, $this->requestUrl, $matches);
        if($matches){
            return $matches[1][0];
        }

        return '';
    }

    /**
     * @throws \ErrorException
     */
    public function download():void
    {
        $vid = $this->getVid();
        $ckey = $this->getCKey();
        $clientTs = $this->getClientTs();
        $utid = $this->getUtid();
        $httpReferer = 'https://tv.youku.com';
        $ccode = '0517';

        $youkuVideoUrl = 'https://ups.youku.com/ups/get.json?vid='. $vid .'&ccode=' . $ccode . '&client_ip=192.168.1.1&client_ts=' . $clientTs .'&utid=' . $utid .'&ckey=' . $ckey;

        $json = self::get($youkuVideoUrl, $httpReferer, $vid);

        if(isset($json['data']['stream'])){
            $videosTitle = $json['data']['video']['title'];

            $this->setVideosTitle($videosTitle);

            $videosInfo = ArrayHelper::multisort($json['data']['stream'], 'width', SORT_DESC);

            $this->videoQuality = $videosInfo[0]['stream_type'];
            $this->outputVideosTitle();

            foreach($videosInfo[0]['segs'] as $key => $value){
                $fileSize = $value['size'];
                $fileName = $this->videosTitle . '-' . $key;

                $fileOptions = [
                    'fileSize' => $fileSize,
                ];

                $this->writeFileLog($fileName.$this->fileExt)->downloadFile($value['cdn_url'], $fileName, [], $fileOptions);

            }

            FFmpeg::concatToMp4($this->videosTitle, $this->ffmpFileListTxt, './Videos/');

            $this->deleteTempSaveFiles();
            $this->success($this->ffmpFileListTxt);

        } else {
            printf("\n\e[41m%s\033[0m\n", $json);
            exit(0);
        }
    }

    /**
     * @param string $url
     * @param string $httpReferer
     * @param string $cacheFileName
     * @return bool|mixed|null
     * @throws \ErrorException
     */
    public static function get(string $url,string $httpReferer,string $cacheFileName)
    {
        $ch = curl_init();
        $defaultOptions = Curl::defaultOptions($url, $httpReferer);

        curl_setopt_array($ch, $defaultOptions);
        $contents = curl_exec($ch);
        curl_close($ch);

        $contents = json_decode($contents, true);

        if(isset($contents['data']['error']['note'])){
            $contentsCache = (new FileCache())->get($cacheFileName);
            if($contentsCache){
                $contents = $contentsCache;
            } else {
                $contents = $contents['data']['error']['note'];
            }
        } else if(is_array($contents['data']['stream'])) {
            (new FileCache())->set($cacheFileName, $contents, 1800);
        }

        return $contents;
    }

}