<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/5
 * Time: 15:36
 */

namespace core\Platform\Youtube;

use core\Common\Downloader;
use core\Config\Config;
use core\Http\Curl;

class Youtube extends Downloader
{
    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    public function getUrl()
    {

    }

    /**
     * @throws \ErrorException
     */
    public function download(): void
    {
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

        $info = Curl::get($this->requestUrl, $this->requestUrl, $curlProxy);

        var_dump($info);


        exit(0);

    }

}