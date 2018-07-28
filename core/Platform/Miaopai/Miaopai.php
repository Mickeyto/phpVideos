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

            $this->downloadUrls[0] = $this->requestUrl;

            $this->outputVideosTitle();
            $this->downloadFile();
            $this->success($this->ffmpFileListTxt);
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

            //https://kscdn.miaopai.com/stream/xBghjLxNWzMYqIcEH0D5FDmMttMmBejfSo-nRw__.mp4?ssig=e52e308ef953d7b90898f1aa044555af&time_stamp=1531901900853

            $gotoN = 1;
            gotoVideosDownload:
            $this->downloadUrls[0] = $videosUrl;
            $vi = $this->downloadFile(); //下载

            if($vi['fileSize'] == 1024 && $gotoN < 2){
                $videosUrl = str_replace(['txycdn'], 'kscdn', $vi['info']['url']);
                $gotoN++;
                goto gotoVideosDownload;
            }

            $this->success($this->ffmpFileListTxt);

            libxml_use_internal_errors($errors);

        } else {
            throw new \ErrorException('not found page');
        }

    }

}