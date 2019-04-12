<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/8/5
 * Time: 18:05
 */

namespace core\Platform\Twitter;


use core\Common\ArrayHelper;
use core\Common\Downloader;
use core\Common\FFmpeg;
use core\Common\M3u8;
use core\Config\Config;
use core\Http\Curl;
use \ErrorException;

class Twitter extends Downloader
{
    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    /**
     * @return null|string
     */
    public function getVid():?string
    {
        $urlPath = parse_url($this->requestUrl, PHP_URL_PATH);
        $vid = pathinfo($urlPath, PATHINFO_BASENAME);

        if(!is_numeric($vid)){
            $this->error('Error：vid not number');
        }

        return $vid;
    }

    /**
     * @param string $vid
     * @return array
     * @throws ErrorException
     */
    public function getVideos(string $vid):array
    {
        $authorization = 'Bearer AAAAAAAAAAAAAAAAAAAAANRILgAAAAAAnNwIzUejRCOuH5E6I8xnZz4puTs%3D1Zv7ttfk8LF81IUq16cHjhLTvJu4FA33AGWWjCpTnA';

        $api = 'https://api.twitter.com/1.1/statuses/show.json?include_profile_interstitial_type=1&include_blocking=1&include_blocked_by=1&include_followed_by=1&include_want_retweets=1&include_mute_edge=1&include_can_dm=1&skip_status=1&cards_platform=Web-12&include_cards=1&include_ext_alt_text=true&include_reply_count=1&tweet_mode=extended&trim_user=false&include_ext_media_color=true&id='. $vid .'&ext=mediaStats,highlightedLabel';

        $curlHeader = $this->httpHeader($authorization);
        $getInfo = Curl::get($api, $this->requestUrl, $curlHeader);
        if(empty($getInfo[0])){
            $this->error('Error：get videos info ');
        }

        $json = json_decode($getInfo[0], true);
        if(isset($json['errors'])){
            $this->error('Error：' . $json[0]['message']);
        }

        $userName = str_replace([' ', '\\', '/', '\''], '', $json['user']['name']);

        $title = $userName . '-' . $vid;
        $videoInfo = $json['extended_entities']['media'][0]['video_info']['variants'];

        return [
            'title' => $title,
            'list' => $videoInfo,
        ];
    }

    public function httpHeader(string $authorization=''):array
    {
        $httpProxy = Config::instance()->get('http_proxy');
        $ip = Curl::randIp();
        $curlHeader = [
            CURLOPT_HTTPHEADER => [
                "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                "Accept-Language: zh-CN,en-US;q=0.7,en;q=0.3",
                "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36",
                "HTTP_X_FORWARDED_FOR: {$ip}",
                "CLIENT-IP: {$ip}",
                "Authorization: {$authorization}",
            ],
            CURLOPT_PROXY => $httpProxy,
        ];

        return $curlHeader;
    }

    /**
     * @param array $videosInfo
     * @return array
     * @throws ErrorException
     */
    public function getM3u8(array $videosInfo):array
    {
        $curlHeader = $this->httpHeader();
        $baseHost = parse_url($videosInfo['application/x-mpegURL'][0]['url'], PHP_URL_HOST);
        $m3u8Urls = Curl::get($videosInfo['application/x-mpegURL'][0]['url'], $this->requestUrl, $curlHeader);

        $result = [];
        if($m3u8Urls[0]){
            $m3u8Urls = M3u8::getUrls($m3u8Urls[0]);
            $url = array_pop($m3u8Urls);
            $m3u8Url = $baseHost . trim($url);

            $m3u8Urls = Curl::get($m3u8Url, $this->requestUrl, $curlHeader);
            if(empty($m3u8Urls[0])){
                $this->error('Error：m3u8 empty');
            }

            $m3u8Url = M3u8::getUrls($m3u8Urls[0]);
            if(!empty($m3u8Url)){
                foreach($m3u8Url as $row){
                    $result[] = 'https://' . $baseHost . trim($row);
                }
            } else {
                $this->error('Error：m3u8 empty');
            }
        }

        return $result;
    }

    /**
     * @throws ErrorException
     */
    public function download(): void
    {
        $vid = $this->getVid();

        $videoInfo = $this->getVideos($vid);

        $videosInfoGroup = ArrayHelper::group($videoInfo['list'], 'content_type');

        if(isset($videosInfoGroup['video/mp4'])){
            $videoUrlInfo = ArrayHelper::multisort($videosInfoGroup['video/mp4'], 'bitrate', SORT_DESC);

            $mp4Info = array_shift($videoUrlInfo);

            $this->downloadUrls[0] = $mp4Info['url'];
            $this->videoQuality = $mp4Info['content_type'];
        } else {
            $m3u8Urls = $this->getM3u8($videosInfoGroup);

            $this->downloadUrls = $m3u8Urls;
            $this->fileExt = '.ts';
        }

        $fileSizeArray = [
            'totalSize' => self::DEFAULT_FILESIZE,
            'list' => [self::DEFAULT_FILESIZE],
        ];
        $httpProxy = Config::instance()->get('http_proxy');
        $curlProxy = [];
        if($httpProxy){
            $curlProxy = [
                CURLOPT_PROXY => $httpProxy,
            ];
        }

        $this->setVideosTitle($videoInfo['title']);
        $downloadFileInfo = $this->downloadFile($fileSizeArray, $curlProxy);

        if($downloadFileInfo < 1024){
            printf("\n\e[41m%s\033[0m\n", 'Errors：download file 0');
        } else {
            if($this->fileExt == '.ts'){
                FFmpeg::concatToMp4($this->videosTitle, $this->ffmpFileListTxt, './Videos/');
                $this->deleteTempSaveFiles();
            }
        }

        $this->success($this->ffmpFileListTxt);
    }

}