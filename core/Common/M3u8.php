<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/22
 * Time: 15:19
 */

namespace core\Common;


class M3u8
{
    /**
     * @param string $contents
     * @return array|null
     */
    public static function getUrls(string $contents):?array
    {
        $pattern = '/\n[0-9a-zA-Z](.*?)[^\s]*/i';
        preg_match_all($pattern, $contents, $matches);
        
        if(isset($matches[0]) && is_array($matches[0])){
            return $matches[0];
        }
        
        return null;
    }
}