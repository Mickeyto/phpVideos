<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/5
 * Time: 16:08
 */
namespace core\Http;

class Domain
{
    public static function match(string $url,int $index=1):?string
    {
        $preg = '/([a-z0-9][-a-z0-9]{0,62})\.(com\.cn|com\.hk|cn|com|net|edu|gov|biz|org|info|pro|name|xxx|xyz|be|me|top|cc|tv|tt)/';

        preg_match_all($preg, $url, $matches);

        if(count($matches) > 1){
            if(!empty($matches[$index])){
                $domain = $matches[$index][0];
                $domain = ucwords($domain);
                return $domain;
            }
        }

        return null;
    }
}