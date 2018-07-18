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
    public $videoQuality = '';

    /**
     * @var CliProgressBar
     */
    public $cliProgressBar;


    public function download():void
    {

    }

    /**
     * @param string $url
     * @param string $fileName
     * @param array $options
     * @param array $fileOptions
     * @return mixed
     */
    public function downloadFile(string $url,string $fileName,array $options=[], $fileOptions=[])
    {
        $this->outputVideosTitle('Download File', $fileName);
        $this->outputVideoQuality();

        $this->checkFileExists();

        $this->cliProgressBar = new CliProgressBar();
        if(isset($fileOptions['fileSize'])){
            $this->fileSize = $fileOptions['fileSize'];
            $this->cliProgressBar->setStep($fileOptions['fileSize']);
        }

        $this->checkDirectory();

        $file = $this->rootPath . $fileName . $this->fileExt;
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
    public function checkDirectory():void
    {
        if(!is_dir($this->rootPath)){
            mkdir($this->rootPath, 0777, true);
        }
    }

    /**
     * 检查文件是否已存在
     */
    public function checkFileExists():void
    {
        if($this->videosTitle){
            $filePath = $this->rootPath . $this->videosTitle . $this->fileExt;
            if(file_exists($filePath)){
                if(is_file($this->ffmpFileListTxt)){
                    unlink($this->ffmpFileListTxt);
                }
                echo "\033[0;32mThis folder already contains a file named\033[0m".PHP_EOL;
                exit(0);
            }
        }
    }

    /**
     * @param string $videosTitle
     * @return Downloader
     */
    public function setVideosTitle(string $videosTitle):self
    {
        $this->videosTitle = str_replace([' ', '\\', '/'], '',$videosTitle);

        return $this;
    }

    /**
     * @param string $head
     * @param string $titleName
     */
    public function outputVideosTitle(string $head='Videos Title',string $titleName='')
    {
        if(empty($titleName)){
            $titleName = $this->videosTitle;
        }

        echo PHP_EOL . "{$head}：    \e[0;32m{$titleName}\e[0m". PHP_EOL;
    }

    public function outputVideoQuality()
    {
        echo PHP_EOL . "Video Quality：      \033[0;32m{$this->videoQuality}\033[0m   ". PHP_EOL . PHP_EOL;
    }

    /**
     * @return string
     */
    public static function randIp():string
    {
        return rand(50,250).".".rand(50,250).".".rand(50,250).".".rand(50,250);
    }

    /**
     * @param $fileName
     * @return array
     */
    final static function defaultOptions($fileName):array
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
    public function writeFileLog(string $fileName, $path='./'):self
    {
        $fileContents = "file ./Videos/{$fileName}".PHP_EOL;
        $file = $path . md5($this->requestUrl) . '-filelist.txt';

        $this->ffmpFileListTxt = $file;
        file_put_contents($file, $fileContents,FILE_APPEND | LOCK_EX);

        return $this;
    }

    /**
     * 删除临时下载文件
     */
    public function deleteTempSaveFiles():void
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
        if(is_file($fileListText)){
            unlink($fileListText);
        }

        $tr = PHP_EOL;
        printf("\033[0;32mDownload Done\033[0m{$tr}");
    }

    public function __destruct()
    {
        unset($this->cliProgressBar);
    }

}