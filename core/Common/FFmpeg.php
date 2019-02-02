<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/9
 * Time: 21:31
 */

namespace core\Common;


class FFmpeg
{
    /**
     * @param string $outFileName
     * @param string $outputPath
     * @param string $fileListText
     * @return string
     */
    public static function concatToMp4(string $outFileName,string $fileListText, string $outputPath='./Videos/'):?string
    {
        $outFileName .= '.mp4';
        $outputPath .= $outFileName;

        $command = "ffmpeg -y -f concat -safe -1 -i {$fileListText} -c copy -bsf:a aac_adtstoasc '" . $outputPath .'\' 2>&1 ';
        $execInfo = shell_exec($command);

        return $execInfo;
    }

}