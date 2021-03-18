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

$domain = 'Haokan';
$className = "\core\Platform\\$domain\\" . $domain;

$classFile = __DIR__ . str_replace('\\', DIRECTORY_SEPARATOR, $className);
if(!file_exists($classFile.'.php')){
    echo("\033[31m  No Support \033[0m".PHP_EOL);
    exit(0);
}


$contents = file_get_contents('haokan.json');
$contents = json_decode($contents, true);
$_data = $contents['column/detail']['data'];
$_authorTitle = str_replace(' ', '', $_data['title']);

/**
 * @var $class \core\Common\Downloader
 */
foreach($_data['items'] as $_lists){
    $_hdurls = isset($_lists['video_list']['1080p']) ? $_lists['video_list']['1080p'] : $_lists['video_list']['hd'];
    $_titles = str_replace(' ', '', $_lists['title']);
    $_titles .= '-'. str_replace(' ', '', $_lists['ext']['episodes']);

    $class = new $className($_hdurls);
    $class->videosTitle = $_authorTitle . '-' . $_titles;
    $class->download();
}
