<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/6
 * Time: 22:17
 */

namespace core\Http;


use core\Http\MessageInterface\ServerRequest;

class Client implements ServerRequest
{
    /**
     * 返回请求参数
     * @return array
     */
    public function getServerParams():array
    {

        return [];
    }

    /**
     * 返回 cookie 参数
     * @return array
     */
    public function getCookiesParams():array
    {

        return [];
    }

    /**
     * 在原有的基础上增加新的 cookies
     * @param array $cookiesParams
     * @return array
     */
    public function withCookiesParams(array $cookiesParams):array
    {

        return [];
    }

    /**
     * 返回查询参数
     * @return array
     */
    public function getQueryParams():array
    {

        return [];
    }

    /**
     * @param array $queryParams
     * @return mixed
     */
    public function withQueryParams(array $queryParams)
    {

        return [];
    }

    /**
     * 返回上传文件
     * @return array
     */
    public function getUploadFiles():array
    {

        return [];
    }

    public function withUploadFiles(array $uploadFiles):array
    {

        return [];
    }

    public function getParsedBody()
    {

    }

    public function withParsedBody($parsedBody)
    {

    }

    public function getAttributes()
    {

    }

    public function getAttribute($name, $default=null)
    {

    }

    public function withAttribute($name, $value)
    {

    }

    public function withoutAttribute($name)
    {

    }

    /**
     * 获取消息请求 URI
     * @return string
     */
    public function getRequestTarget():string
    {

        return '';
    }

    /**
     * 返回一个指定的请求 URI
     * @return self
     */
    public function withRequestTarget()
    {
        return $this;
    }


    /**
     * 返回 http 方法
     * @return string
     */
    public function getMethod():string
    {

        return '';
    }

    /**
     * 返回更改了请求方法的消息实例
     * @return self
     */
    public function withMethod()
    {
        return $this;
    }

    /**
     * 返回 URI 实例
     * @return mixed
     */
    public function getUri()
    {

        return '';
    }

    /**
     * 返回修改了 URI 的消息实例
     * @return mixed
     */
    public function withUri()
    {
        return '';
    }

    /**
     * 获取 http 协议版本信息
     * @return mixed
     */
    public function getProtocolVersion()
    {
        return '';
    }

    /**
     * @return mixed
     */
    public function withProtocolVersion()
    {

        return '';
    }

    /**
     * 返回指定的头信息，
     * @param string $name
     * @param string $value
     * @return mixed
     */
    public function withHeader(string $name, string $value)
    {

        return '';
    }

    /**
     * 向头信息增加新值
     * @param string $name
     * @param string $value
     * @return mixed
     */
    public function withAddedHeader(string $name, string $value)
    {

        return '';
    }


    public function withoutHeader(string $name):array
    {

        return [];
    }

    /**
     * 获取所有头部信息
     * @return array
     */
    public function getHeaders():array
    {

        return [];
    }

    /**
     * 根据指定的名称返回一条头信息
     * @param string $name
     * @return array
     */
    public function getHeader(string $name):array
    {

        return [];
    }

    /**
     * 检查头信息是否存在该值
     * @param string $name
     * @return mixed
     */
    public function hasHeader(string $name):bool
    {

        return false;
    }

    /**
     * 返回一条头信息，以『,』号隔开
     * @param string $name
     * @return mixed|string
     */
    public function getHeaderLine(string $name):?string
    {

        return '';
    }

    /**
     * 获取 http 消息内容
     * @return mixed
     */
    public function getBody()
    {

        return '';
    }

    /**
     * @return mixed
     */
    public function withBody()
    {

        return '';
    }


}