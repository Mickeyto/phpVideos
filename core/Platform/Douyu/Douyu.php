<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/5
 * Time: 15:40
 */
namespace core\Platform\Douyu;

use core\Cache\FileCache;
use core\Common\Downloader;
use core\Http\Curl;

class Douyu extends Downloader
{
    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    /**
     * @return string
     * @throws \ErrorException
     */
    public function getDid():string
    {
        $defaultDid = '10000000000000000000000000001501';
        //client_id=1|6
        $didApiUrl = 'https://passport.douyu.com/lapi/did/api/get?client_id=1';

        $didCache = (new FileCache())->get($didApiUrl);
        if($didCache){
            return $didCache;
        }

        $getInfo = Curl::get($didApiUrl, 'https://v.douyu.com', [], false);
        if($getInfo){
            $json = json_decode($getInfo[0], true);
            if(!isset($json['data']['did'])){
                throw new \ErrorException('Could not get did');
            }

            $defaultDid = $json['data']['did'];
            (new FileCache())->set($didApiUrl, $defaultDid, 7200);
        }

        return $defaultDid;
    }

    /**
     * @throws \ErrorException
     */
    public function download():void
    {
        $urlInfo = parse_url($this->requestUrl, PHP_URL_PATH);

        $urlArray = explode('show/', $urlInfo);

        $contents = file_get_contents('./playlist.m3u');

        preg_match_all('/[^#](.*?)\s/', $contents, $matches);

        var_dump($matches);
    }

}