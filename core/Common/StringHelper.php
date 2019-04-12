<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/8/6
 * Time: 18:06
 */

namespace core\Common;


class StringHelper
{
    /**
     * @param string $str
     * @param string $replace
     * @param array $search
     * @return string
     */
    public static function replace(string $str, $replace='',array $search = [' ', '\\', '/', '\'', '&', ')', '(', '*'])
    {
        $newString = str_replace($search, $replace, $str);
        return $newString;
    }

}