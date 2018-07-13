<?php
include_once "autoLoad.php";

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