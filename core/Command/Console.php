<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/16
 * Time: 22:54
 */

namespace core\Command;


class Console
{
    public static function stdin()
    {
        return rtrim(fgets(STDIN), PHP_EOL);
    }

    public static function stdout($string)
    {
        return fwrite(STDOUT, $string);
    }

    public static function select(string $question, array $options):?string
    {
        return '';
    }

}