<?php
include_once "autoLoad.php";

function checkVersion()
{
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
    $className = "\core\Platform\\$domain\\" . $domain;

    $class = new $className($url);
    $class->download();
}

echo "\n";