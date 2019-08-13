[中文文档](README_CN.md)
# phpVideos

|   SITE  |   VIDEO  |
|   :-----:  |  :---:   |
|   Youku   |  :white_check_mark:  |
|   Miaopai  |   :white_check_mark:  |
|   QQ  |   :white_check_mark:  |
|   XVideos |   :white_check_mark:  |
|   Pornhub |  :white_check_mark: |
|   91porn   |   :white_check_mark:  |
|   微博酷燃    | :white_check_mark: |
|   Douyu  |   :white_check_mark:  |
|   Iqiyi  |   :white_check_mark:  |
|   Weibo  |   :white_check_mark:  |
|   Twitter  |   :white_check_mark:  |
|   Bilibili（B站）  |   :white_check_mark:  |
|   Toutiao  |   :white_check_mark:  |
|   MGTV  |   :white_check_mark:  |
# Installation
*   PHP >= 7.1.3
*   OpenSSL PHP Extension
*   cURL PHP Extension
*   FFmpeg：
    *   Mac：brew install ffmpeg
    *   Linux:  [Download](http://ffmpeg.org/download.html)
*   cURL  >= 7.37.0

#   Discord
Discord：https://discord.gg/xvNQPaT

#   http proxy config
```bash
cp config-template.php config.php
```
*  config.php
```php
return [
    'http_proxy' => '127.0.0.1:1087',
    'weiboCookie' => '',

    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36',

    //91porn
    '91porn' => [
        'cookie' => '',
        'user_agent' => '',
    ]
];
```
    
#   Usage
php start.php 'link_address'

php start.php 'link_address' -i //show playlist
![image](https://image.ibb.co/mysKyd/Jul_21_2018_21_38_34.gif)

#   Other
CentOS 7
<pre>
rpm -ivh http://mirror.city-fan.org/ftp/contrib/yum-repo/city-fan.org-release-2-1.rhel7.noarch.rpm
yum --enablerepo=city-fan.org update curl
</pre>

# Windows10
https://youtu.be/KPKoTLtGNOs
