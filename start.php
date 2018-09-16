<?php
include_once "autoLoad.php";

function checkVersion()
{
    if(!function_exists('curl_version')){
        die("PHP version\e[31m must enabled curl extension\e[0m\n");
    }

    $curlVersion = curl_version()['version'];
    $phpVersion = version_compare(PHP_VERSION, '7.1.3', '>=');

    if(('7.37.0' > $curlVersion) && !$phpVersion){
        die("PHP version\e[31m must >= 7.1 and curl version >= 7.37.0\e[0m\n");
    }
}

checkVersion();

$args = array_slice($argv, 1);
if(empty($args)){
    printf("请传入参数 \n");
    die;
}

$url = $args[0];
$domain = parse_url($url, PHP_URL_HOST);

$domain = \core\Http\Domain::match($url);

if($domain){
    if(in_array($domain, ['91p25', '91porn'])){
        $domain = 'Porn';
    }

    $className = "\core\Platform\\$domain\\" . $domain;

    $classFile = __DIR__ . str_replace('\\', DIRECTORY_SEPARATOR, $className);
    if(!file_exists($classFile.'.php')){
        echo("\033[31m  No such file or directory \033[0m".PHP_EOL);
        exit(0);
    }

    $class = new $className($url);
    $class->download();
}

echo "\n";