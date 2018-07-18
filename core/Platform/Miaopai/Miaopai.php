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
use core\Common\FFmpeg;
use core\Http\Curl;

class Miaopai extends Downloader
{
    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    /**
     * @throws \ErrorException
     */
    public function download():void
    {
        $parseUrlPath = parse_url($this->requestUrl, PHP_URL_PATH);
        $pathInfoExtension = pathinfo($parseUrlPath, PATHINFO_EXTENSION);

        if($pathInfoExtension == 'mp4'){
            $this->videosTitle = 'MP-' . md5($this->requestUrl);

            $this->downloadFile($this->requestUrl, $this->videosTitle);
            $this->success();
            exit(0);
        }

        $res = Curl::get($this->requestUrl, $this->requestUrl);

        if($res){
            preg_match_all('/"videoSrc":"(.*?)",/i', $res[0], $matches);
            if(!isset($matches[1]) && !is_array($matches[1])){
                (new FileCache())->delete($this->requestUrl);

                throw new \ErrorException('无法解析该地址');
            }

            $errors = libxml_use_internal_errors(true);

            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->recover = true;
            $dom->strictErrorChecking = false;
            $dom->loadHTML($res[0]);

            $element = $dom->documentElement;
            $titleItem = $element->getElementsByTagName('title');

            if($titleItem->length < 1 || !isset($matches[1][0])){
                (new FileCache())->delete($this->requestUrl);
                throw new \ErrorException('无法解析该地址');
            }

            $videosTitle = $titleItem->item(0)->textContent;

            $this->setVideosTitle($videosTitle);

            $videosUrl = $matches[1][0];
            $fileName = $this->videosTitle . '-0';

            $this->writeFileLog($fileName.$this->fileExt)->downloadFile($videosUrl, $fileName); //下载

            FFmpeg::concatToMp4($this->videosTitle, './Videos/', $this->ffmpFileListTxt);

            $this->deleteTempSaveFiles();   //删除临时保存文件
            $this->success($this->ffmpFileListTxt);

            libxml_use_internal_errors($errors);

        } else {
            throw new \ErrorException('not found page');
        }

    }

}