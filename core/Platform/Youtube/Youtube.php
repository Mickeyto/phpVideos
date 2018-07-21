<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/5
 * Time: 15:36
 */

namespace core\Platform\Youtube;

use core\Common\Downloader;

class Youtube extends Downloader
{
    public function __construct(string $url)
    {
        $this->requestUrl = $url;
    }

    public function download(): void
    {
        $this->downloadFile('https://r1---sn-i3b7knl6.googlevideo.com/videoplayback?gir=yes&ei=Z9ZSW-25MIqzqQGn-J-oBA&itag=136&sparams=aitags,clen,dur,ei,gir,id,ip,ipbits,itag,keepalive,lmt,mime,mm,mn,ms,mv,pl,requiressl,source,expire&requiressl=yes&key=yt6&source=youtube&mime=video/mp4&pl=17&keepalive=yes&aitags=133,134,135,136,137,160,242,243,244,247,248,278&lmt=1532013137222978&expire=1532177095&ipbits=0&mm=31&mn=sn-i3b7knl6&c=WEB&id=o-AOfAHGSg8qgQRH3QIpc09I9KZNUPhkyCUhqCZ9ZAPITQ&mt=1532155431&mv=m&dur=201.993&ms=au&ip=35.229.151.220&clen=34719477&alr=yes&signature=D3AD1B809EB0748FE0F7B5BDB121D3CB08E1EB73.896AAAFD34BC854E0E27472B27699687D462699E&cpn=yxKsxahfB0xq2VNP&cver=2.20180719&rn=0&rbuf=0&range=0-34719477', 'temp-youtube');
        $this->success();

        exit(0);

    }

}