<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/28
 * Time: 00:43
 */

namespace core\Config;


class Config
{
    public $conf;
    public static $instance;

    public function __construct()
    {
        $config = self::includeConfig();

        $this->conf = new \ArrayObject($config);
    }

    /**
     * @return Config
     */
    public static function instance():self
    {
        if(null === self::$instance){
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * @return array|null
     */
    public static function includeConfig():?array
    {
        $baseDir = realpath(getcwd());
        $configFile = $baseDir . '/config.php';
        if(file_exists($configFile)){
            $config = include_once $configFile;
            return $config;
        }

        return null;
    }

    public function get($key)
    {
        if(!$this->conf->offsetExists($key)){
            return false;
        }

        return $this->conf->offsetGet($key);
    }
}