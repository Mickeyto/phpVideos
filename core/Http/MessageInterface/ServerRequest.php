<?php
/**
 * Created by PhpStorm.
 * User: mickey
 * Date: 2018/7/6
 * Time: 20:47
 */

namespace core\Http\MessageInterface;



interface ServerRequest extends Request
{
    /**
     * 返回请求参数
     * @return array
     */
    public function getServerParams():array ;

    /**
     * 返回 cookie 参数
     * @return array
     */
    public function getCookiesParams():array ;

    /**
     * 在原有的基础上增加新的 cookies
     * @param array $cookiesParams
     * @return array
     */
    public function withCookiesParams(array $cookiesParams):array ;

    /**
     * 返回查询参数
     * @return array
     */
    public function getQueryParams():array ;

    /**
     * @param array $queryParams
     * @return mixed
     */
    public function withQueryParams(array $queryParams);

    /**
     * 返回上传文件
     * @return array
     */
    public function getUploadFiles():array ;

    public function withUploadFiles(array $uploadFiles):array ;

    public function getParsedBody();

    public function withParsedBody($parsedBody);

    public function getAttributes();

    public function getAttribute($name, $default=null);

    public function withAttribute($name, $value);

    public function withoutAttribute($name);

}