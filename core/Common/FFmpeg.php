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

    /**
     * 合并音、视频
     * @param string $videoFile
     * @param string $audioFile
     * @param string $fileName
     * @param string $outputPath
     * @param string $fileExt
     * @return string
     */
    public static function mergeVideoAudio(string $videoFile,string $audioFile,string $fileName,  string $outputPath='./Videos/', $fileExt='mp4'):?string
    {
        $fileName .= '.' . $fileExt;
        $outputPath .= $fileName;

        $command = "ffmpeg -y -i {$videoFile} -i {$audioFile} -vcodec copy -acodec copy '" . $outputPath .'\' 2>&1 ';
        $execInfo = shell_exec($command);

        return $execInfo;
    }

}