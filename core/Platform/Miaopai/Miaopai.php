<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/5
 * Time: 15:40
 */
namespace core\Platform\Miaopai;

use core\Common\Downloader;
use core\Common\FFmpeg;
use core\Http\Curl;

class Miaopai extends Downloader
{
    public $url = '';

    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * @throws \ErrorException
     */
    public function download()
    {
        $res = Curl::get($this->url, $this->url);

        if($res){
            preg_match_all('/"videoSrc":"(.*?)",/i', $res[0], $matches);
            if(!isset($matches[1]) && !is_array($matches[1])){
                $errors = sprintf("\033[0;31mm无法解析该地址\033[0m");

                throw new \ErrorException($errors);
            }

            $errors = libxml_use_internal_errors(true);

            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->recover = true;
            $dom->strictErrorChecking = false;
            $dom->loadHTML($res[0]);

            $element = $dom->documentElement;
            $titleItem = $element->getElementsByTagName('title');

            $videosTitle = $titleItem->item(0)->textContent;

            $this->setVideosTitle($videosTitle);

            $videosUrl = $matches[1][0];
            $fileExt = explode('?', $videosUrl)[0];
            $fileExt = '.' . pathinfo($fileExt, PATHINFO_EXTENSION);
            $fileName = $this->videosTitle . '-0' . $fileExt;

            $this->writeFileLog($fileName)->downloadFile($videosUrl, $fileName); //下载

            FFmpeg::concatToMp4($this->videosTitle, './Videos/', $this->ffmpFileListTxt);

            $this->deleteTempSaveFiles();   //删除临时保存文件
            $this->success($this->ffmpFileListTxt);

            libxml_use_internal_errors($errors);

        } else {
            throw new \ErrorException('not found page');
        }

    }

}