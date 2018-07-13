<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/6
 * Time: 21:32
 */

namespace core\Http\MessageInterface;


interface Uri
{
    /**
     * 从 uri 返回 scheme，小写字母
     * @return string
     */
    public function getScheme():string ;

    public function withScheme($scheme);

    /**
     * 返回 uri 授权信息
     * @return string
     */
    public function getAuthority():string ;

    /**
     * 返回 uri 用户信息
     * @return string
     */
    public function getUserInfo():string ;

    /**
     * @param $user
     * @param string $password
     * @return string
     */
    public function withUserInfo($user, $password=''):string ;

    /**
     * 返回 uri host 信息
     * @return string
     */
    public function getHost():string ;

    /**
     * @param $host
     * @return string
     */
    public function withHost($host):string ;

    /**
     * 返回 uri 端口
     * @return int
     */
    public function getPort():int ;

    /**
     * @param $port
     * @return int
     */
    public function withPort($port):int ;

    /**
     * 返回 uri 路径
     * @return string
     */
    public function getPath():string ;

    /**
     * @param $path
     * @return string
     */
    public function withPath($path):string ;

    /**
     * 返回 uri 请求字符串
     * @return mixed
     */
    public function getQuery();

    public function withQuery($query):string ;

    /**
     * @return string
     */
    public function getFragment():string ;

    /**
     * @param $fragment
     * @return string
     */
    public function withFragment($fragment):string ;

    public function __toString();

}