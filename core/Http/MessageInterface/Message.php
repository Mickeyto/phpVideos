<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/6
 * Time: 19:41
 */

namespace core\Http\MessageInterface;


interface Message
{
    /**
     * 获取 http 协议版本信息
     * @return mixed
     */
    public function getProtocolVersion();

    /**
     * @return mixed
     */
    public function withProtocolVersion();

    /**
     * 返回指定的头信息，
     * @param string $name
     * @param string $value
     * @return mixed
     */
    public function withHeader(string $name, string $value);

    /**
     * 向头信息增加新值
     * @param string $name
     * @param string $value
     * @return mixed
     */
    public function withAddedHeader(string $name, string $value);


    public function withoutHeader(string $name):array ;

    /**
     * 获取所有头部信息
     * @return array
     */
    public function getHeaders():array ;

    /**
     * 根据指定的名称返回一条头信息
     * @param string $name
     * @return array
     */
    public function getHeader(string $name):array ;

    /**
     * 检查头信息是否存在该值
     * @param string $name
     * @return mixed
     */
    public function hasHeader(string $name):bool ;

    /**
     * 返回一条头信息，以『,』号隔开
     * @param string $name
     * @return mixed|string
     */
    public function getHeaderLine(string $name):?string ;

    /**
     * 获取 http 消息内容
     * @return mixed
     */
    public function getBody();

    /**
     * @return mixed
     */
    public function withBody();

}