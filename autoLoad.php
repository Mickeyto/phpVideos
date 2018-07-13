<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/5
 * Time: 16:14
 */

/**
 * @param $className
 */
function autoload($className)
{
    $baseDir = __DIR__;

    $className = ltrim($className, '\\');
    $className = str_replace('\\', '/', $className);

    $fileName  = '';
    $namespace = '';
    if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }


    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
    if(!is_file($fileName)){

        $fileName = $namespace . '.php';
    }

    require $baseDir . '/' . $fileName;
}

spl_autoload_register('autoload');