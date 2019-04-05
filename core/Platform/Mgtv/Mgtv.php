<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2019-03-07
 * Time: 16:01
 */
namespace core\Platform\Mgtv;


use core\Cache\FileCache;
use core\Common\ArrayHelper;
use core\Common\Downloader;
use core\Common\FFmpeg;
use core\Common\M3u8;
use core\Http\Curl;
use \ErrorException;

class Mgtv extends Downloader
{
    public $vid = '';
    private $stkuuid = 'aa11eb5d-6ddd-4c96-8305-fbf3dd6910c7';

    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    public function getTime()
    {
        return time();
    }

    /**
     * @param int $clt
     * @return string
     */
    public function generateTk2(int $clt):string
    {
        $params = 'did='. $this->stkuuid .'|pno=1030|ver=0.3.0301|clit=' . $clt;
        $params = base64_encode($params);
        $params = str_replace('/\+/i', '-', $params);
        $params = str_replace('/\//i', '~', $params);
        $params = str_replace('/=/i', '-', $params);

        $paramsToArray = str_split($params);
        $reverse = array_reverse($paramsToArray);
        $tk2 = implode($reverse, '');

        return $tk2;
    }

    /**
     * 匹配 url Vid
     */
    public function matchUrlVid():void
    {
        preg_match('/\/\d+\/(\d+)[\.html]/', $this->requestUrl, $matches);

        $this->vid = isset($matches[1]) ? $matches[1] : '';
    }

    /**
     * 匹配 html 页面 vid
     * @return string
     * @throws ErrorException
     */
    public function matchHtmlVidAndTitle():string
    {
        $vid = '';
        $response = Curl::get($this->requestUrl, $this->requestUrl);
        if(empty($response[0])){
            $this->error('match html vid error');
        }
        
        preg_match('/vid:(.*?)[,]/', $response[0], $matches);
        preg_match('/title:(.*?)[,]/', $response[0], $matchesTitle);
        if(isset($matchesTitle[1])){
            $this->setVideosTitle($matchesTitle[1]);
        } else {
            $this->setVideosTitle('mgtv-' . $this->vid);
        }

        $vid = trim($matches[1]);

        return $vid;
    }

    /**
     * @return array
     */
    public function httpHeader()
    {
        $ip = Curl::randIp();
        return [
            CURLOPT_HTTPHEADER => [
                "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                "Accept-Language:zh-CN,en-US;q=0.7,en;q=0.3",
                "User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36",
                "HTTP_X_FORWARDED_FOR:{$ip}",
                "CLIENT-IP:{$ip}",
                "Cookie:PM_CHKID=f8401deff531c85e;__STKUUID={$this->stkuuid}",
            ]
        ];
    }

    /**
     * @param int $clt
     * @return mixed
     * @throws ErrorException
     */
    public function getVideoInfo(int $clt)
    {
        if(empty($this->vid)){
            $this->vid = $this->matchHtmlVidAndTitle();
        }

        $urlParams = 'tk2=' . $this->generateTk2($clt);
        $urlParams .= '&video_id=' . $this->vid . '&_support=10000000';

        $apiUrl = 'https://pcweb.api.mgtv.com/player/video?' . $urlParams;

        $response = Curl::get($apiUrl, $this->requestUrl, $this->httpHeader());

        if(empty($response[0])){
            $this->error($response[0]);
        }

        $json = json_decode($response[0], true);

        if($json['code'] != 200){
            $this->error($json['msg']);
        }

        $json['data']['title'] = $this->videosTitle;
        return $json;
    }

    /**
     * @return array|string|null
     * @throws ErrorException
     */
    public function getSource()
    {
        $cacheKey = 'mgtv-' . $this->vid;
        $cache = (new FileCache())->get($cacheKey);
        if($cache){
            return $cache;
        }

        $clt = $this->getTime();
        $videoInfo = $this->getVideoInfo($clt);

        $params = '&tk2=' . $this->generateTk2($clt);
        $params .= '&pm2=' . $videoInfo['data']['atc']['pm2'] . '&video_id=' . $this->vid;

        $apiUrl = 'https://pcweb.api.mgtv.com/player/getSource?_support=10000000';
        $apiUrl .= $params;

        $response = Curl::get($apiUrl, $this->requestUrl, $this->httpHeader());
        if(empty($response[0])){
            $this->error('getSource error');
        }

        $json = json_decode($response[0], true);
        if($json['code'] != 200){
            $this->error($json['msg']);
        }

        $json['data']['title'] = $videoInfo['data']['title'];
        (new FileCache())->set($cacheKey, $json, 300);

        return $json;
    }

    /**
     * @return array
     * @throws ErrorException
     */
    public function getM3u8UrlInfo():array
    {
        $source = $this->getSource();
        $stream = [
            'stream_domain' => $source['data']['stream_domain'],
            'stream' => []
        ];
        foreach($source['data']['stream'] as $row){
            if(empty($row['url'])){
                continue;
            }
            $stream['stream'][] = $row;
        }

        $stream['stream'] = ArrayHelper::multisort($stream['stream'], 'filebitrate', SORT_DESC);

        $totalStreamDomain = count($stream['stream_domain']);
        $m3u8Domain = $stream['stream_domain'][$totalStreamDomain - 1];

        $requestUrl = $m3u8Domain . $stream['stream'][0]['url'];

        $response = Curl::get($requestUrl, $this->requestUrl);

        if(empty($response[0])){
            $this->error('get m3u8 url error');
        }

        $json = json_decode($response[0], true);
        if($json['status'] != 'ok'){
            $this->error($json['status']);
        }

        return [
            'domain' => $m3u8Domain,
            'url' => $json['info'],
            'title' => $source['data']['title'],
            'quality' => $stream['stream'][0]['name']
        ];
    }

    /**
     * @param array $m3u8Url
     * @return array
     * @throws ErrorException
     */
    public function getM3u8Ts(array $m3u8Url)
    {
        $cacheKey = 'mgtv-m3u8' . $this->vid;
        $m3u8Urls = '';
        $cache = (new FileCache())->get($cacheKey);

        if(!$cache){
            $requestUrl = $m3u8Url['url'];
            $domain = pathinfo($requestUrl, PATHINFO_DIRNAME);

            $response = Curl::get($requestUrl, $this->requestUrl);
            if(empty($response[0])){
                $this->error('get m3u8 ts error');
            }

            $cacheContent = [
                'm3u8Urls' => $response[0],
                'domain' => $domain,
            ];

            (new FileCache())->set($cacheKey, $cacheContent, 300);
            $cache = $cacheContent;
        }
        
        $m3u8Urls = $cache['m3u8Urls'];
        
        $totalSize = 0;
        $sizeList = [];
        preg_match_all('/\#EXT-MGTV-File-SIZE:(\d+)/', $m3u8Urls, $matches);

        foreach($matches[1] as $row){
            $totalSize += $row;
            $sizeList[] = $row;
        }

        $urls = M3u8::getUrls($m3u8Urls);
        $tsList = [];
        foreach($urls as $row){
            $tsList[] = $cache['domain'] . '/' .ltrim($row);
        }

        return [
            'sizeInfo' => [
                'totalSize' => $totalSize,
                'sizeList' => $sizeList,
            ],
            'tsList' => $tsList
        ];
    }

    /**
     * @throws ErrorException
     */
    public function download(): void
    {
        $m3u8UrlInfo = $this->getM3u8UrlInfo();

        $m3u8Ts = $this->getM3u8Ts($m3u8UrlInfo);

        $this->downloadUrls = $m3u8Ts['tsList'];
        $this->setVideosTitle($m3u8UrlInfo['title']);

        $this->videoQuality = $m3u8UrlInfo['quality'];

        $this->fileExt = '.ts';
        $fileSizeArray = [
            'totalSize' => $m3u8Ts['sizeInfo']['totalSize'],
            'list' => $m3u8Ts['sizeInfo']['sizeList'],
        ];
        $downloadFileInfo = $this->downloadFile($fileSizeArray);

        if($downloadFileInfo < 1024){
            printf("\n\e[41m%s\033[0m\n", 'Errors：download file 0');
        } else {
            FFmpeg::concatToMp4($this->videosTitle, $this->ffmpFileListTxt, './Videos/');
        }

        $this->deleteTempSaveFiles();
        $this->success($this->ffmpFileListTxt);
    }

}