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
    /**
     * @return string
     */
    public static function stdin():string
    {
        return rtrim(fgets(STDIN), PHP_EOL);
    }

    /**
     * @param $string
     * @return bool|int
     */
    public static function stdout($string):?int
    {
        return fwrite(STDOUT, $string);
    }

    /**
     * options[
     *  'y' => 'yes', 'n' => 'no'
     * ]
     * return: y(yes)/n(no)
     * @param string $question
     * @param array $options
     * @param int $colors
     * @return null|string
     */
    public static function selected(string $question, array $options,int $colors=32):?string
    {
        $temp = [];
        foreach($options as $key => $value){
            $temp[] = "\033[{$colors}m$key\033[0m" . '（' . $value . '）';
        }

        gotoSelected:
        $outQuestion = $question . implode('/ ', $temp) . '：';
        self::stdout($outQuestion);

        $stdin = self::stdin();
        if(!array_key_exists($stdin, $options)){
            goto gotoSelected;
        }

        return $stdin;
    }

}