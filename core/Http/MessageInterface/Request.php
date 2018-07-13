<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/6
 * Time: 19:37
 */
namespace core\Http\MessageInterface;


interface Request extends Message
{
    /**
     * 获取消息请求 URI
     * @return string
     */
    public function getRequestTarget():string ;

    /**
     * 返回一个指定的请求 URI
     * @return self
     */
    public function withRequestTarget();

    /**
     * 返回 http 方法
     * @return string
     */
    public function getMethod():string ;

    /**
     * 返回更改了请求方法的消息实例
     * @return self
     */
    public function withMethod();

    /**
     * 返回 URI 实例
     * @return mixed
     */
    public function getUri();

    /**
     * 返回修改了 URI 的消息实例
     * @return mixed
     */
    public function withUri();

}