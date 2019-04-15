<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/6
 * Time: 10:50
 */
namespace core\Platform\Porn;

use core\Cache\FileCache;
use core\Common\Downloader;
use core\Config\Config;
use core\Http\Curl;
use \ErrorException;
use core\Common\StringHelper;

class Porn extends Downloader
{
    public $_str = '';
    public $_str2 = '';
    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    public function base64Decode(string $str):string
    {
        return base64_decode($str);
    }

    /**
     * 解密设置参数
     * @param string $str1
     * @param string $str2
     * @param mixed ...$arg
     * @return Porn
     */
    public function setStr(string $str1, string $str2, ...$arg):self
    {
        $this->_str = $this->base64Decode(trim($str1, '"'));
        $this->_str2 = trim($str2, '"');
        return $this;
    }

    /**
     * 解密 code
     * @return string
     */
    public function generateCode():string
    {
        $_code = '';
        $strLen = strlen($this->_str);
        $str2Len = strlen($this->_str2);

        for($i = 0; $i < $strLen; $i++){
            $k = $i % $str2Len;
            $_code .= StringHelper::fromCharCode(ord($this->_str[$i]) ^ ord($this->_str2[$k]));
        }

        return $_code;
    }

    /**
     * 解析资源
     * @param string $arg
     * @return string
     */
    public function strencode(string $arg)
    {
        $array = explode(',', $arg);
        $this->setStr($array[0], $array[1]);
        $code = $this->generateCode();

        return $code;
    }

    /**
     * @param array $curlProxy
     * @return array|bool|null
     * @throws ErrorException
     */
    public function getVideosUrl(array $curlProxy=[]):?array
    {
        $videosUrlCache = (new FileCache())->get($this->requestUrl);
        if($videosUrlCache){
            return $videosUrlCache;
        }

        $html = Curl::get($this->requestUrl, $this->requestUrl, $curlProxy, false);

        if(empty($html[0])){
            $this->error('Error：not found html');
        }

        preg_match_all('/<div\sid="viewvideo-title">\s*(.*)\s*<\/div>/', $html[0], $matchesTitle);
        if(!isset($matchesTitle[1][0])){
            $this->error('Error：not found title');
        }

        preg_match_all('/<source\ssrc="(.*)"\stype="video\/mp4">/', $html[0], $matchesVideo);
        if(!isset($matchesVideo[1][0])){
            preg_match_all('/strencode\((.*)\)\);/', $html[0], $matchesVideo);
            if(!isset($matchesVideo[1][0])){
                $this->error('Error：not found mp4 source');
            } else {
                $param = $matchesVideo[1][0];
                $code = $this->base64Decode($this->strencode($param));
                preg_match_all('/src=\'(.*)\'\stype=\'video\/mp4\'>/', $code, $matchesVideo);
            }
        }

        $title = str_replace(PHP_EOL, '', $matchesTitle[1][0]);
        $this->setVideosTitle($title);

        $videosUrl = $matchesVideo[1][0];
        $videosInfo = [
            'url' => $videosUrl,
            'title' => $this->videosTitle,
        ];

        if(!$videosUrl){
            $this->error('Error：videos url is empty');
        }

        (new FileCache())->set($this->requestUrl, $videosInfo);

        return $videosInfo;

    }

    /**
     * @throws ErrorException
     */
    public function download():void
    {
        $httpProxy = Config::instance()->get('http_proxy');
        $curlProxy = [];
        if($httpProxy){
            $curlProxy = [
                CURLOPT_PROXY => $httpProxy,
            ];
        }

        $videosUrl = $this->getVideosUrl($curlProxy);

        $this->videosTitle = $videosUrl['title'];
        $this->downloadUrls[0] = $videosUrl['url'];

        $this->downloadFile(['totalSize' => self::DEFAULT_FILESIZE, 'list' => [self::DEFAULT_FILESIZE]], $curlProxy);
        $this->success($this->ffmpFileListTxt);
    }

}