<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/5
 * Time: 15:37
 */
namespace core\Http;

use core\Cache\FileCache;

class Curl
{
    /**
     * @param string $url
     * @param string $httpReferer
     * @return array|null
     * @throws \ErrorException
     */
    public static function get(string $url,string $httpReferer):?array
    {
        $cache = (new FileCache())->get($url);

        if($cache){
            $contents = $cache;
            $curlInfo = [
                'http_code' => 200,
            ];
        } else {
            $ch = curl_init();
            $defaultOptions = self::defaultOptions($url, $httpReferer);
            curl_setopt_array($ch, $defaultOptions);
            $contents = curl_exec($ch);
            $curlInfo = curl_getinfo($ch);

            curl_close($ch);

            if($curlInfo['http_code'] != 200){
                $contents = null;
            }

            (new FileCache())->set($url, $contents);
        }

        return [
            $contents,
            $curlInfo,
        ];
    }

    public static function post()
    {

    }

    public static function randIp()
    {
        return rand(50,250).".".rand(50,250).".".rand(50,250).".".rand(50,250);
    }

    /**
     * @param $url
     * @param $httpReferer
     * @param bool|string $ip
     * @return array
     */
    public static function defaultOptions($url, $httpReferer, $ip=false)
    {
        if(!$ip){
            $ip = self::randIp();
        }

        return [
            CURLOPT_URL => $url,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_REFERER => $httpReferer,
            CURLOPT_HEADEROPT => [
                "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                "Accept-Encoding: gzip, deflate, br",
                "Accept-Language: zh-CN,en-US;q=0.7,en;q=0.3",
                "Host: https://v.youku.com",
                "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36",
                "HTTP_X_FORWARDED_FOR: {$ip}"
            ]
        ];
    }

}