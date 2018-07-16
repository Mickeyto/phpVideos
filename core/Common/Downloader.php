<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/6
 * Time: 00:36
 */
namespace core\Common;


use core\Command\CliProgressBar;

class Downloader
{
    const DEFAULT_FILESIZE = 1024;

    public $rootPath = './Videos/';
    public $fileExt = '.mp4';
    public $fileSize = 0;
    public $tempSaveFiles = [];
    public $ffmpFileListTxt = './filelist.txt';
    public $videosTitle = '';
    public $requestUrl = '';

    /**
     * @var CliProgressBar
     */
    public $cliProgressBar;


    public function download()
    {

    }

    /**
     * @param string $url
     * @param string $fileName
     * @param array $options
     * @param array $fileOptions
     * @return string
     */
    public function downloadFile(string $url,string $fileName,array $options=[], $fileOptions=[])
    {
        $this->outputVideosTitle($fileName);

        $this->checkFileExists();

        $this->cliProgressBar = new CliProgressBar();
        if(isset($fileOptions['fileSize'])){
            $this->fileSize = $fileOptions['fileSize'];
            $this->cliProgressBar->setStep($fileOptions['fileSize']);
        }

        $this->checkDirectory();

        $file = $this->rootPath . $fileName;
        $defaultOptions = self::defaultOptions($file);
        if($options){
            array_merge($defaultOptions, $options);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, $defaultOptions);

        curl_exec($ch);
        $curlInfo = curl_getinfo($ch);

        curl_close($ch);

        array_push($this->tempSaveFiles, $file);
        return $curlInfo;
    }

    /**
     * 检查目录是否存在，如不存在则创建
     */
    public function checkDirectory()
    {
        if(!is_dir($this->rootPath)){
            mkdir($this->rootPath, 0777, true);
        }
    }

    /**
     * 检查文件是否已存在
     */
    public function checkFileExists()
    {
        if($this->videosTitle){
            $filePath = $this->rootPath . $this->videosTitle . $this->fileExt;
            if(file_exists($filePath)){
                $this->success();
                exit(0);
            }
        }
    }

    /**
     * @param string $videosTitle
     */
    public function outputVideosTitle(string $videosTitle)
    {
        echo "{$videosTitle}：\n";
    }

    public static function randIp()
    {
        return rand(50,250).".".rand(50,250).".".rand(50,250).".".rand(50,250);
    }

    /**
     * @param $fileName
     * @return array
     */
    final static function defaultOptions($fileName)
    {
        $fp = fopen($fileName, 'w+');
        $ip = self::randIp();
        return [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADEROPT => [
                "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                "Accept-Encoding: gzip, deflate, br",
                "Accept-Language: zh-CN,en-US;q=0.7,en;q=0.3",
                "Host: v.youku.com",
                "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36",
                "HTTP_X_FORWARDED_FOR: {$ip}"
            ],
            CURLOPT_NOPROGRESS => false,
            CURLOPT_PROGRESSFUNCTION => [self::class, 'progress'],
            CURLOPT_FILE => $fp,
        ];
    }

    /**
     * @param $ch
     * @param $expectedDownloadByte
     * @param $currentDownloadByte
     * @param $expectedUploadFileSize
     * @param $currentUploadFileSize
     * @return bool|int
     */
    final function progress($ch, $expectedDownloadByte, $currentDownloadByte, $expectedUploadFileSize, $currentUploadFileSize)
    {
        $this->cliProgressBar->progress($currentDownloadByte);

        if(empty($expectedDownloadByte)){
            $this->fileSize = self::DEFAULT_FILESIZE;
            $this->cliProgressBar->setStep($this->fileSize);
        }

        if($this->fileSize < $expectedDownloadByte && $expectedDownloadByte > 0){
            $this->fileSize = $expectedDownloadByte;
            $this->cliProgressBar->setStep($this->fileSize);
        }

        if($this->fileSize === $currentDownloadByte){
            $this->cliProgressBar->end();
            return 1;
        }

        return false;
    }

    /**
     * @param string $fileName
     * @param string $path
     * @return $this
     */
    public function writeFileLog(string $fileName, $path='./filelist.txt')
    {
        $fileContents = "file ./Videos/{$fileName}".PHP_EOL;
        file_put_contents($path, $fileContents,FILE_APPEND | LOCK_EX);

        return $this;
    }

    /**
     * 删除临时下载文件
     */
    public function deleteTempSaveFiles()
    {
        if(!empty($this->tempSaveFiles)){
            foreach($this->tempSaveFiles as $value){
                $tr = PHP_EOL;
                printf("\033[0;34mUnlink file：{$value}\033[0m{$tr}");
                unlink($value);
            }
        }
    }

    /**
     * @param string $fileListText
     */
    public function success(string $fileListText=null)
    {
        //清空文件内容
        if($fileListText){
            file_put_contents($fileListText, '', LOCK_EX);
        }

        $tr = PHP_EOL;
        printf("\033[0;32mDownload Done\033[0m{$tr}");
    }

    public function __destruct()
    {

    }

}