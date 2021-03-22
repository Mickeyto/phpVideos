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
$_authorTitle = '';
$_data['items'] = [];
if(isset($contents['videolistall'])){
    $_data['items'] = $contents['videolistall']['data']['results'];
}
if(isset($contents['column/detail'])){
    $_data = $contents['column/detail']['data'];
    $_authorTitle = str_replace(' ', '', $_data['title']);
}

/**
 * @var $class \core\Common\Downloader
 */
$_count = count($_data['items']);
$_run = 0;
foreach($_data['items'] as $_lists){
    $_run++;
    $_videoList = isset($_lists['content']) ? $_lists['content'] : $_lists;
    $_authorTitle = empty($_authorTitle) ? $_videoList['author'] : $_authorTitle;
    $_videoQuality = isset($_videoList['video_list']['1080p']) ? '1080p' : 'hd';
    $_hdurls = isset($_videoList['video_list']['1080p']) ? $_videoList['video_list']['1080p'] : $_videoList['video_list']['hd'];
    $_titles = str_replace(' ', '', $_videoList['title']);
    $_titles .= '-'. str_replace(' ', '', !empty($_videoList['ext']) ? $_videoList['ext']['episodes'] : '');

    $class = new $className($_hdurls);
    $class->videosTitle = $_authorTitle . '-' . $_titles;
    $class->videoQuality = sprintf('%d/%d-%s', $_run, $_count, $_videoQuality);
    $class->download();
}
