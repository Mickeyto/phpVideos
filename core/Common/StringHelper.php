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

    /**
     * 返回相对应于 ascii 对应的字符创建的字符串
     * @param int ...$intCode
     * @return string
     */
    public static function fromCharCode(int ...$intCode)
    {
        $char = '';
        foreach($intCode as $value){
            $char .= chr($value);
        }

        return $char;
    }

    /**
     * RC4 加密算法
     * @param $pwd
     * @param $code
     * @return string
     */
    public static function rc4($pwd, $code):string
    {
        $key[] ="";
        $box[] ="";
        $cipher='';
        $pwd_length = strlen($pwd);
        $data_length = strlen($code);
        for ($i = 0; $i < 256; $i++)
        {
            $key[$i] = ord($pwd[$i % $pwd_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++)
        {
            $j = ($j + $box[$i] + $key[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $data_length; $i++)
        {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $k = $box[(($box[$a] + $box[$j]) % 256)];
            $cipher .= chr(ord($code[$i]) ^ $k);
        }

        return $cipher;
    }

}