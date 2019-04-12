<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/8/5
 * Time: 15:38
 */

namespace core\Platform\Weibo;


use core\Command\Console;
use core\Common\ArrayHelper;
use core\Common\Downloader;
use core\Config\Config;
use core\Http\Curl;
use \ErrorException;

class Weibo extends Downloader
{
    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    /**
     * @return string
     */
    public function getVid():string
    {
        $vid = '';
        $url = parse_url($this->requestUrl, PHP_URL_PATH);

        $urls = explode('v/', $url);
        if(isset($urls[2])){
            $vid = $urls[2];
        }

        if(empty($vid)){
            $urls = explode('/', $url);
            if(isset($urls[2])){
                $vid = $urls[2];
            }
        }

        return $vid;
    }

    /**
     * 需用户登录 cookie，匹配 html video
     * @date 2019-04-10
     * @param string $html
     * @param string $pattern
     * @return array
     */
    public function matchHtmlVideo(string $html, $pattern='/video-sources="fluency=(.*?)"/'):array
    {
        preg_match_all($pattern, $html, $matches);
        if(!isset($matches[1][0])){
            $this->error('无法获取视频，请更新配置文件 weiboCookie 值');
        }
        $urlInfo = urldecode($matches[1][0]);

        preg_match_all('/&480=(.*?)video&/i', $urlInfo, $video_480);
        preg_match_all('/&720=(.*?)video&/i', $urlInfo, $video_720);
        preg_match_all('/<div\sclass="info_txt\sW_f14">(.*)<\/div>/', $html, $matchTitle);

        $videoTitle = isset($matchTitle[1][0]) ? $matchTitle[1][0] : md5($urlInfo);

        $videoInfo = [
            'type' => 'login',
            'title' => $videoTitle
        ];
        if(isset($video_480[1][0])){
            $videoInfo['videoInfo'][480] = [
                'qType' => 480,
                'url' => $video_480[1][0] . 'video&'
            ];
        }

        if(isset($video_720[1][0])){
            $videoInfo['videoInfo'][720] = [
                'qType' => 720,
                'url' => $video_720[1][0] . 'video&'
            ];
        }

        return $videoInfo;
    }

    /**
     * url：https://m.weibo.cn/statuses/show?id=Gte2peqo6
     * @param string $vid
     * @return array|null
     * @throws ErrorException
     */
    public function getVideosInfo(string $vid):?array
    {
        $getJsonUrl = 'https://m.weibo.cn/statuses/show?id=' . $vid;

        $getInfo = Curl::get($getJsonUrl, $this->requestUrl);
        $videosInfo = [];

        if(!empty($getInfo[0])){
            $json = json_decode($getInfo[0], true);
            if(1 != $json['ok']){
                $cookie = Config::instance()->get('weiboCookie');
                if(empty($cookie)){
                    Console::stdout('请输入 Cookie：');
                    $cookie = Console::stdin();
                }

                if(!empty($cookie)){
                    $header = [
                        CURLOPT_COOKIE => $cookie,
                    ];
                    $html = Curl::get($this->requestUrl, $this->requestUrl, $header);

                    $urlInfo = $this->matchHtmlVideo($html[0]);

                    return $urlInfo;
                }

                $this->error('Error：json is error / html must login');
            }

            if(empty($json['data']['page_info']['media_info'])){
                $this->error('Error：media_info is empty');
            }

            $mediaInfo = array_values($json['data']['page_info']['media_info']);
            $mediaInfo = array_slice($mediaInfo, 0, count($mediaInfo)-1);
            $streamInfo = array_keys($json['data']['page_info']['media_info']);
            $streamInfo = array_slice($streamInfo, 0, count($streamInfo)-1);

            $videoTitle = md5($this->requestUrl);
            if(!empty($json['data']['page_info']['title'])){
                $videoTitle = $json['data']['page_info']['title'];
            }

            $videosInfo = [
                'type' => 'api',
                'title' => $videoTitle,
                'url' => $mediaInfo,
                'size' => $json['data']['page_info']['video_details']['size'],
                'stream' => $streamInfo,
            ];
        }

        return $videosInfo;
    }

    /**
     * html5 Url: https://m.weibo.cn/status/Gte2peqo6?fid=1034%3A4269653577684456&jumpfrom=weibocom
     * @throws ErrorException
     */
    public function download(): void
    {
        $vid = $this->getVid();

        if(empty($vid)){
            $this->error('Error：vid is empty');
        }

        $videosInfo = $this->getVideosInfo($vid);

        if(empty($videosInfo)){
            $this->error('Error：VideosInfo is empty');
        }

        $this->setVideosTitle($videosInfo['title']);

        if($videosInfo['type'] == 'api'){
            $this->videoQuality = array_pop($videosInfo['stream']);
            $this->downloadUrls[0] = array_pop($videosInfo['url']);
            if(empty($this->downloadUrls[0])){
                $this->downloadUrls[0] = array_shift($videosInfo['url']);
                $this->videoQuality = array_shift($videosInfo['stream']);
            }
        } else {
            $videoList = ArrayHelper::multisort($videosInfo['videoInfo'], 'qType', SORT_DESC);
            $this->videoQuality = $videoList[0]['qType'];
            $this->downloadUrls[0] = $videoList[0]['url'];
        }

        $downloadFileInfo = $this->downloadFile();

        if($downloadFileInfo < 1024){
            printf("\n\e[41m%s\033[0m\n", 'Errors：download file 0');
        }

        $this->success($this->ffmpFileListTxt);
    }

}