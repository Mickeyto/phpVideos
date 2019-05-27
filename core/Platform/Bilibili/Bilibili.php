<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2019-02-26
 * Time: 09:38
 */
namespace core\Platform\Bilibili;

use core\Cache\FileCache;
use core\Common\ArrayHelper;
use core\Common\Downloader;
use core\Common\FFmpeg;
use core\Http\Curl;
use \ErrorException;

class Bilibili extends Downloader
{
    public $referer = 'https://www.bilibili.com';
    public $avid='';
    public $cid = '';
    public $pages = [];

    public function __construct(string $url)
    {
        $this->requestUrl = $url . '/';
        $this->initAvid();
    }

    public function initAvid():void
    {
        preg_match_all("/video\/av(.+?)[\/ | \?]/i", $this->requestUrl, $matchAid);

        $this->avid = isset($matchAid[1]) ? $matchAid[1][0] : '';
    }

    /**
     * @note https://api.bilibili.com/x/web-interface/view?aid=44640089
     * @throws ErrorException
     */
    public function setApiCid():void
    {
        if(empty($this->avid)){
            $this->error('aid is empty');
        }

        $aidApi = 'https://api.bilibili.com/x/web-interface/view?aid=' . $this->avid;
        $response = Curl::get($aidApi, $this->referer);

        if(empty($response[0])){
            $this->error('cid is empty');
        }

        $json = json_decode($response[0], true);

        $pages = [];
        if(count($json['data']['pages']) > 0){
            $pages = $json['data']['pages'];
        } else {
            $pages[] = ['cid' => $json['data']['cid']];
        }

        $this->pages = $pages;
        $this->videosTitle = $json['data']['title'];
    }

    /**
     * 爬取下来的是非 html 内容，暂不使用
     * @return array|null
     * @throws ErrorException
     */
    public function matchHtml():?array
    {
        $response = Curl::get($this->requestUrl, $this->requestUrl);
        preg_match_all('/window.__INITIAL_STATE__=(.+?);/', $response[0], $matches);

        return $matches;
    }

    /**
     * @note https://api.bilibili.com/x/player/playurl?avid=44640089&cid=78142881&fnver=0&fnval=16&type=&otype=json
     * @param string $cid
     * @return array|mixed|string|null
     * @throws ErrorException
     */
    public function getPlayurl($cid='')
    {
        $cacheKey = $this->avid.'bilibili-'.$cid;
        $playurlCache = (new FileCache())->get($cacheKey);
        if($playurlCache){
            return $playurlCache;
        }

        $playurlApi = 'https://api.bilibili.com/x/player/playurl?avid='. $this->avid .'&cid='. $cid .'&fnver=0&fnval=16&type=&otype=json';
        $response = Curl::get($playurlApi, $this->referer);

        if(empty($response[0])){
            $this->error('get playurl error');
        }

        $json = json_decode($response[0], true);
        $json['title'] = $this->videosTitle;
        (new FileCache())->set($cacheKey, $json);

        return $json;
    }

    /**
     * @return array
     * @throws ErrorException
     */
    public function multiPageUrl():array
    {
        //pages
        $pagesUrl = [];
        $cacheKey = $this->avid.'bilibili-' . $this->avid;
        $playurlCache = (new FileCache())->get($cacheKey);
        if($playurlCache){
            return $playurlCache;
        }

        if(count($this->pages) > 0){
            foreach($this->pages as $row){
                $fileSizeArray = [];
                $downloadUrls = [];
                $durl = false;
                $urlJson = $this->getPlayurl($row['cid']);
                if(isset($urlJson['data']['durl'])){
                    $durl = true;
                    $videoQuality = 'flv';
                    $downloadUrls = [$urlJson['data']['durl'][0]['url']];
                    $fileSizeArray = [
                        'totalSize' => $urlJson['data']['durl'][0]['size'],
                        'list' => 1024,
                    ];
                } else {
                    $videoUrlList = ArrayHelper::multisort($urlJson['data']['dash']['video'], 'width', SORT_DESC);
                    $audioList = $urlJson['data']['dash']['audio'];

                    $videoQuality = $videoUrlList[0]['height'];
                    $downloadUrls = [
                        'video_audio' =>[
                            $videoUrlList[0]['baseUrl'],
                            $audioList[0]['baseUrl']
                        ]
                    ];
                }

                $videoTitle = $urlJson['title'] . str_replace([' ', '\\', '/', '\'', '&'], '', $row['part']);

                $pagesUrl[] = [
                    'title' => $videoTitle,
                    'cid' => $row['cid'],
                    'file' => $fileSizeArray,
                    'videoQuality' => $videoQuality,
                    'url' => $downloadUrls,
                    'durl' => $durl
                ];
            }
        }

        //save cache
        (new FileCache())->set($cacheKey, $pagesUrl, 300);

        return $pagesUrl;
    }

    /**
     * @return array
     * @throws ErrorException
     */
    public function initPlaylist():array
    {
        $this->setApiCid();
        $pagesUrl = $this->multiPageUrl();
        $this->playlist = $pagesUrl;

        return $pagesUrl;
    }

    /**
     * @throws ErrorException
     */
    public function outPlaylist()
    {
        $this->initPlaylist();
        parent::outPlaylist(); // TODO: Change the autogenerated stub
    }

    /**
     * @param null $argvOpt
     * @throws ErrorException
     */
    public function download($argvOpt=null): void
    {
        $pagesUrl = $this->initPlaylist();

        //show playlist
        if(isset($argvOpt['i'])){
            $this->outPlaylist();
        }

        $tr = PHP_EOL;
        $pagesUrlCount = count($pagesUrl);
        printf("{$tr}\033[0;32mTotal {$pagesUrlCount}\033[0m{$tr}");

        if($pagesUrlCount > 0){
            foreach($pagesUrl as $row){
                $title = $row['cid'] . '-' . $row['title'];
                $this->setVideosTitle($title);
                $this->videoQuality = $row['videoQuality'];
                
                if(isset($row['url']['video_audio'])){
                    $this->downloadUrls = $row['url']['video_audio'];
                } else {
                    $this->downloadUrls = $row['url'];
                }
                $fileSizeArray = [];
                if(count($row['file']) > 0){
                    $fileSizeArray = $row['file'];
                }

                $this->fileExt = '.flv';
                $header = [
                    CURLOPT_REFERER => $this->referer,
                ];
                $downloadFileInfo = $this->downloadFile($fileSizeArray, $header);

                if($downloadFileInfo < 1024){
                    printf("\n\e[41m%s\033[0m\n", 'Errors：download file 0');
                } else {
                    if(!$row['durl']){
                        $videoFile = './Videos/'. $this->videosTitle .'-0' . $this->fileExt;
                        $audioFile = './Videos/'. $this->videosTitle .'-1' . $this->fileExt;

                        FFmpeg::mergeVideoAudio($videoFile, $audioFile, $this->videosTitle);
                        $this->deleteTempSaveFiles();
                    }
                }

                $this->tempSaveFiles = [];
                $this->success($this->ffmpFileListTxt);
            }
        }
    }

}