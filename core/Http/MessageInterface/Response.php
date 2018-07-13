<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/6
 * Time: 19:42
 */

namespace core\Http\MessageInterface;


interface Response extends Message
{
    /**
     * 获取 http 响应状态码
     * @return mixed
     */
    public function getStatusCode();

    public function withStatusCode($statusCode, $reasonPhrase='');

    public function getReasonPhrase();

}