<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/18
 * Time: 14:27
 */

namespace core\Platform\Krcom;


use core\Cache\FileCache;
use core\Common\ArrayHelper;
use core\Common\Downloader;
use core\Http\Curl;

class Krcom extends Downloader
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
        $fileKey = md5($this->requestUrl);

        $htmlFile = false;
        if(file_exists('./Runtime/Cache/Html/'.$fileKey.'.html')){
            $htmlFile = './Runtime/Cache/Html/'.$fileKey.'.html';
        }

        if(!$htmlFile){
            $c = Curl::get($this->requestUrl, $this->requestUrl, [], false);
            if(!$c){
                throw new \ErrorException('无法获取 HTML 内容');
            }
            $htmlFile = (new FileCache())->setFileDir('Html/')->setFile($this->requestUrl, $c[0]);
        }

        $libxmlErros = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();

        $dom->loadHTMLFile($htmlFile);

        $videosTitle = $dom->getElementsByTagName('title')->item(0)->nodeValue;
        $el = $dom->documentElement->getElementsByTagName('script');

        libxml_use_internal_errors($libxmlErros);

        if($el->length < 17){
            throw new \ErrorException('无法解析内容');
        }


        $javaScript = $el->item(12)->textContent;

        $patter = "/video-sources=(.*?)action-data=/is";
        preg_match_all($patter, $javaScript, $matches);

        $videosUrlList = str_replace(['\n', '\"'], '', $matches[1][0]);

        $videosUrlList = explode('video&', $videosUrlList);

        $tempArray = [];
        foreach($videosUrlList as $row){
            $temp = explode('=', $row);
            if(is_numeric($temp[0])){
                $tempUrl = rtrim(str_replace('video', '', $temp[1]));

                $tempArray[] = [
                    'plate' => $temp[0],
                    'url' => 'http:' . urldecode($tempUrl) . 'video',
                ];
            }
        }

        if(!$tempArray){
            throw new \ErrorException('获取视频地址失败');
        }

        $tempArray = ArrayHelper::multisort($tempArray, 'plate', SORT_DESC);
        $this->setVideosTitle($videosTitle);
        $this->videoQuality = $tempArray[0]['plate'];

        $this->downloadFile($tempArray[0]['url'], $this->videosTitle);

        $this->success();

        unlink($htmlFile);

    }
}