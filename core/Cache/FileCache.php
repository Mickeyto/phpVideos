<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/7
 * Time: 23:17
 */
namespace core\Cache;

class FileCache
{
    public $rootPath;
    public $flags;
    public $fileDir = 'Html/';

    /**
     * FileCache constructor.
     * @param string $directory
     * @param int $flags
     */
    public function __construct(string $directory='./Runtime/Cache/', int $flags=LOCK_EX)
    {
        $this->rootPath = $directory;
        $this->flags = $flags;
    }

    /**
     * @param $key
     * @param $value
     * @param int $expire second
     * @return bool
     * @throws \ErrorException
     */
    public function set($key, $value,int $expire=0):bool
    {
        if(!is_string($key)){
            throw new \ErrorException("Cache key name must be string");
        }

        if(empty($value)){
            return false;
        }

        $expireTime = $expire;
        if($expire > 0){
            $expireTime = time() + $expire;
        }

        $data = serialize([$value, $expireTime]);

        $fileName = md5($key);
        $saveFilename = $this->rootPath . $fileName;

        $this->checkDirectory();

        file_put_contents($saveFilename, $data, $this->flags);

        return true;
    }

    /**
     * @param string $dir
     * @return FileCache
     */
    public function setFileDir(string $dir):self
    {
        $dir = ltrim($dir, '/');
        $dir = ltrim($dir, '\\');
        $this->fileDir = $dir;

        return $this;
    }

    /**
     * @param string $key
     * @param string $data
     * @param string $suffix
     * @return null|string
     * @throws \ErrorException
     */
    public function setFile(string $key,string $data, string $suffix='.html'):?string
    {
        if(!is_string($key)){
            throw new \ErrorException("Cache key name must be string");
        }

        if(empty($this->fileDir)){
            throw new \ErrorException('fileDir must set');
        }

        $fileName = md5($key);

        $this->rootPath = $this->rootPath . $this->fileDir;
        $saveFilename = $this->rootPath . $fileName . $suffix;

        $this->checkDirectory();

        file_put_contents($saveFilename, $data, $this->flags);

        return $saveFilename;
    }

    public function deleteFile()
    {

    }

    /**
     * @param string $key
     * @param null $default
     * @return array|null|string
     */
    public function get(string $key, $default=null)
    {
        $fileName = md5($key);
        $saveFile = $this->rootPath . $fileName;
        if(!file_exists($saveFile)){
            return false;
        }

        $data = file_get_contents($saveFile);
        $data = unserialize($data);

        $expireTime = $data[1] ?? null;
        $time = time();
        if(!empty($expireTime) && $expireTime < $time){
            $this->delete($key);
            return $default;
        }

        return $data[0];
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete(string $key):bool
    {
        $fileName = md5($key);
        $saveFile = $this->rootPath . $fileName;

        return unlink($saveFile);
    }

    public function checkDirectory():void
    {
        if(!is_dir($this->rootPath)){
            mkdir($this->rootPath, 0777, true);
        }
    }

}