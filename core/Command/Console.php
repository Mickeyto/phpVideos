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
    public static function initArgv():array
    {
        $cmd = [];
        global $argv;
        foreach($argv as $k => $arg){
            if(0 == $k){
                continue;
            }
            switch ($arg){
                case '-i':
                    $cmd['i'] = true;
                    break;
                case '-h':
                    self::usage();
                    break;
                default:
                    $cmd['url'] = $arg;
                    break;
            }
        }

        return $cmd;
    }

    public static function usage()
    {
        echo <<<EOF
Usage:
    -i:     show playlist

Examples:
    show playlist:      php start.php 'video_url' -i
    download video:     php start.php 'video_url'

EOF;
        exit(1);
    }

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