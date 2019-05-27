<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/14
 * Time: 18:43
 */

namespace core\Platform\Qq;


use core\Cache\FileCache;
use core\Common\ArrayHelper;
use core\Common\Downloader;
use core\Http\Curl;
use \ErrorException;

class Qq extends Downloader
{
    const FILE_EXTENSION = '.mp4';

    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    /**
     * @throws ErrorException
     */
    public static function getGuid()
    {
        $getJsonUrl = 'http://ncgi.video.qq.com/fcgi-bin/get_guid_http_to_jce?';
        $guidCache = (new FileCache())->get($getJsonUrl);
        if($guidCache){
            return $guidCache;
        }

        $jsonInfo = Curl::get($getJsonUrl, $getJsonUrl);
        if($jsonInfo){
            $jsonInfo = json_decode($jsonInfo[0], true);
            if($jsonInfo['err_code'] == 0){
                (new FileCache())->set($getJsonUrl, $jsonInfo['guid'], 300);
                return $jsonInfo['guid'];
            }
        }

        throw new ErrorException('无法获取 Guid');
    }

    /**
     * @return bool|string
     * @throws ErrorException
     */
    public function getVid():?string
    {
        $html = Curl::get($this->requestUrl, $this->requestUrl);
        preg_match_all("/url=(.*)?&ptag/i", $html[0], $matches);

        $baseName = pathinfo($matches[1][0], PATHINFO_BASENAME);
        parse_str($baseName, $parseArray);

        if(!isset($parseArray['vid'])){
            $this->error('Error：无法匹配 vid');
        }

        //https://v.qq.com/x/cover/m441e3rjq9kwpsc/h00251u5sdp.html?
        /*$baseName = pathinfo($this->requestUrl, PATHINFO_BASENAME);
        if(!$baseName){
            $this->error('Error：无法匹配 vid');
        }

        $vid = substr($baseName, 0, 11);*/

        return $parseArray['vid'];
    }

    /**
     * @param string $vid
     * @return mixed
     * @throws ErrorException
     */
    public static function getVideosInfo(string $vid)
    {
        $guid = self::getGuid();
        $getVideosInfoUrl = 'https://h5vv.video.qq.com/getinfo?isHLS=false&vid='. $vid .'&otype=ojson&guid='. $guid .'&platform=11&sdtfrom=v1010&host=v.qq.com&_qv_rmt=sNk0sWZTA17002uQa%3D&_qv_rmt2=0Qs65I9%2B149182HOQ%3D';
        
        $videosInfo = Curl::get($getVideosInfoUrl, $getVideosInfoUrl);

        $videosJsonInfo = false;
        if($videosInfo){
            if(!is_array($videosInfo[0])){
                $json = json_decode($videosInfo[0], true);
                $videosInfo[0] = $json;
                (new FileCache())->set($getVideosInfoUrl, $json);
            }

            $videosJsonInfo = $videosInfo[0];
        }


        return $videosJsonInfo;
    }

    /**
     * @param $format
     * @param string $fileName
     * @return mixed
     * @throws ErrorException
     */
    public function getKey($format,string $fileName):array
    {
        //https://h5vv.video.qq.com/getkey?otype=json&vid=t0195b4eoyw&format=10701&filename=t0195b4eoyw.p701.mp4&platform=1
        $getKeyUrl = 'https://h5vv.video.qq.com/getkey?otype=ojson&vid='. $this->getVid() .'&format='. $format .'&filename='. $fileName . '&platform=2';

        $keyInfo = Curl::get($getKeyUrl, $this->requestUrl);
        if($keyInfo){
            if(!is_array($keyInfo[0])){
                $json = json_decode($keyInfo[0], true);
                $keyInfo[0] = $json;
                (new FileCache())->set($getKeyUrl, $json);
            }

            return $keyInfo[0];
        }

        $this->error('Error：无法获取 key');
    }

    /**
     * @param string $keyId
     * @return string
     */
    public function getKeyId(string $keyId):string
    {
        $exKeyId = explode('.', $keyId);

        if($exKeyId[1] > 100000){
            $newKeyId = $exKeyId[0] . '.m' . substr($exKeyId[1], 3) . self::FILE_EXTENSION;
        } elseif($exKeyId[1] > 10000){
            $newKeyId = $exKeyId[0] . '.p' . substr($exKeyId[1], 2) . '.' . $exKeyId[2] . self::FILE_EXTENSION;
        } else {
            switch ($exKeyId[1]){
                case 2:
                    $newKeyId = $exKeyId[0] . self::FILE_EXTENSION;
                    break;
                default:
                    $newKeyId = $keyId . self::FILE_EXTENSION;
                    break;
            }
        }

        return $newKeyId;
    }

    /**
     * @param null $argvOpt
     * @throws ErrorException
     */
    public function download($argvOpt=null):void
    {
        $vid = $this->getVid();
        $videosListInfo = self::getVideosInfo($vid);

        if(!isset($videosListInfo['vl']['vi'][0]['ti'])){
            $this->error("Error：videos title not found");
        }

        $videosTitle = $videosListInfo['vl']['vi'][0]['ti'];

        if(empty($videosTitle)){
            $videosTitle = md5($this->requestUrl);
        }

        if(!isset($videosListInfo['fl']['fi'])){
            $this->error('Error：videos info not found fi');
        }
        $videosArrayCount = count($videosListInfo['fl']['fi']);
        if($videosArrayCount < 1){
            $this->error('Error：videos info not array');
        }

        $this->setVideosTitle($videosTitle);

        $videoFi = ArrayHelper::multisort($videosListInfo['fl']['fi'], 'br', SORT_DESC);

        $videosFileIdPrefix = '';
        $videoFileFormat = $videoFi[0]['id'];
        $this->videoQuality = $videoFi[0]['cname'];

        if($videoFi[0]['id'] > 100000){
            $videosFileIdPrefix = '.m' . substr($videoFi[0]['id'], 3);
        } elseif($videoFi[0]['id'] > 10000){
            $videosFileIdPrefix = '.p'. substr($videoFileFormat, 2);
        }

        $videoFileName = $vid . $videosFileIdPrefix . self::FILE_EXTENSION;

        $videosKey = $this->getKey($videoFileFormat, $videoFileName);

        $uiLen = count($videosListInfo['vl']['vi'][0]['ul']['ui']);

        $videosUrl = $videosListInfo['vl']['vi'][0]['ul']['ui'][$uiLen-1]['url'];
        $fileKeyId = $this->getKeyId($videosKey['keyid']);

        $requestVideosUrl = $videosUrl . $fileKeyId . '?vkey='.$videosKey['key'];

        $this->downloadUrls[0] = $requestVideosUrl;
        $this->playlist = [$requestVideosUrl];

        //show playlist
        if(isset($argvOpt['i'])){
            $this->outPlaylist();
        }

        $downInfo = $this->downloadFile();

        if($downInfo['fileSize'] < 1024){
            unlink($this->rootPath . $this->videosTitle . $this->fileExt);
            $this->error();
        }

        $this->success($this->ffmpFileListTxt);

    }

}