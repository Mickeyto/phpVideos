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

    public function __construct($directory='./Runtime/Cache/', $flags=LOCK_EX)
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
    public function set($key, $value,int $expire=0)
    {
        if(!is_string($key)){
            throw new \ErrorException("Cache key name must be string");
        }

        $expireTime = $expire;
        $data = serialize([$value, $expireTime]);

        $fileName = md5($key);
        $saveFilename = $this->rootPath . $fileName;

        $this->checkDirectory();

        file_put_contents($saveFilename, $data, $this->flags);

        return true;
    }

    public function get($key, $default=null)
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

    public function delete($key)
    {
        $fileName = md5($key);
        $saveFile = $this->rootPath . $fileName;

        return unlink($saveFile);
    }

    public function checkDirectory()
    {
        if(!is_dir($this->rootPath)){
            mkdir($this->rootPath, 0777, true);
        }
    }

}