# phpVideos
php 写的视频下载工具：

|   站点  |   视频  |
|   :-----:  |  :---:   |
|   优酷   |  :white_check_mark:  |
|   秒拍  |   :white_check_mark:  |
|   腾讯  |   :white_check_mark:  |
|   XVideos |   :white_check_mark:  |
|   Pornhub |  :white_check_mark: |
|   91（不提供地址）   |   :white_check_mark:  |
|   微博酷燃    | :white_check_mark: |
|   斗鱼  |   :white_check_mark:  |
|   爱奇艺  |   :white_check_mark:  |
# 环境依赖
*   PHP >= 7.1.3
*   OpenSSL PHP Extension
*   cURL PHP Extension
*   FFmpeg：
    *   Mac：brew install ffmpeg
    *   Linux:  [Download](http://ffmpeg.org/download.html)
*   cURL  >= 7.37.0

#   使用交流
Discord地址（不使用天朝软件）：https://discord.gg/yuxfCy

#   代理配置
*  config.php

    <pre>
    return [
        //http 代理配置
        'http_proxy' => 'http://127.0.0.1:1087',
    ];
    </pre>
    
#   使用方法
php start 'link_address'

![image](https://image.ibb.co/mysKyd/Jul_21_2018_21_38_34.gif)

#   其他
Centos7 默认的 curl 版本是 7.29 的，需要升级。升级方法：
<pre>
rpm -ivh http://mirror.city-fan.org/ftp/contrib/yum-repo/city-fan.org-release-2-1.rhel7.noarch.rpm
yum --enablerepo=city-fan.org update curl
</pre>
curl 升级之后， php 需要重新编译，不能单独编译 php-curl 扩展