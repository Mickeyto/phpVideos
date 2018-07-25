<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/25
 * Time: 16:12
 */

namespace core\Platform\Kuaishou;


use core\Common\Downloader;
use core\Http\Curl;

class Kuaishou extends Downloader
{
    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    /**
     *
     */
    public function download(): void
    {
//        $html = Curl::get($this->requestUrl, $this->requestUrl);

        $html = file_get_contents('./kuaisou.html');

        $libXMlErrors = libxml_use_internal_errors(true);

        //member center https://live.kuaishou.com/u/wwww11111/3xt3mz4aedtairu
        //所有视频列表:https://live.kuaishou.com/profile/wwww11111

        //翻页： post https://live.kuaishou.com/feed/profile
        // params : count(24)\pcursor 变化\principalId(用户ID)\privacy(public)


        //profile videos list
        $dom = new \DOMDocument();
        $dom->loadHTML($html);

        $el = $dom->getElementsByTagName('script');

        libxml_use_internal_errors($libXMlErrors);

        if($el->length > 0){
            for($i=0; $i <= $el->length; $i++){

                if(is_object($el->item($i))){
                    $javascript = $el->item($i)->nodeValue;
                    if(empty($javascript)){
                        echo $i . PHP_EOL;
                    }
                }
            }

        }

    }

}